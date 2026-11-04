<?php

namespace App\Http\Controllers\Tenant\Collector;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantDailyCashHandover;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantCollectorPaymentController extends Controller
{
    /**
     * Resolve active Tenant.
     */
    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }
        return $tenant;
    }

    /**
     * Collect bill from customer on field.
     */
    public function collectBill(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;

        $request->validate([
            'customer_id' => 'required|exists:tenant_customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank,online,pos',
            'trx_id' => 'nullable|string|max:100',
            'discount' => 'nullable|numeric|min:0',
            'months' => 'nullable|integer|min:1|max:36',
            'payment_mode' => 'nullable|string|in:due,monthly,partial,advance,custom',
            'extend_validity' => 'nullable',
            'reactivate_line' => 'nullable',
            'send_sms' => 'nullable',
            'notes' => 'nullable|string|max:500',
        ]);

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('id', $request->customer_id)
            ->firstOrFail();

        $amount = (float) $request->amount;
        $discount = (float) ($request->discount ?? 0);
        $method = $request->payment_method;
        $months = (int) ($request->months ?: 1);
        $shouldExtendValidity = $request->has('extend_validity') ? filter_var($request->extend_validity, FILTER_VALIDATE_BOOLEAN) : true;
        $shouldReactivate = $request->has('reactivate_line') ? filter_var($request->reactivate_line, FILTER_VALIDATE_BOOLEAN) : true;
        $shouldSendSms = $request->has('send_sms') ? filter_var($request->send_sms, FILTER_VALIDATE_BOOLEAN) : true;

        DB::beginTransaction();
        try {
            // 1. Generate Invoice No
            $invoiceNo = 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            if (method_exists(TenantCustomerPayment::class, 'generateInvoiceNo')) {
                try {
                    $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenantId);
                } catch (\Throwable $e) {}
            }

            // 2. Create payment record
            $payment = TenantCustomerPayment::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'reseller_id' => $customer->reseller_id ?? null,
                'invoice_no' => $invoiceNo,
                'billing_month' => Carbon::now()->format('F Y'),
                'amount' => $amount,
                'discount' => $discount,
                'payment_method' => $method,
                'transaction_id' => $request->trx_id ?: ('TRX-' . strtoupper(substr(md5(uniqid()), 0, 10))),
                'collected_by' => $user->id,
                'paid_at' => now(),
                'notes' => $request->notes ?: "Field collection by {$user->name}",
                'status' => 'approved',
            ]);

            // 3. Adjust Customer Due Amount
            $totalRelief = $amount + $discount;
            $oldDue = (float) $customer->due_amount;
            $customer->due_amount = max(0, $oldDue - $totalRelief);

            // 4. Validity Extension Logic
            if ($shouldExtendValidity && $months > 0) {
                $baseExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
                    ? Carbon::parse($customer->expiry_date)
                    : Carbon::today();
                $customer->expiry_date = $baseExpiry->addMonths($months)->endOfDay();
            }

            // 5. Update Status to Active
            if ($shouldReactivate || $customer->due_amount <= 0) {
                $customer->status = 'active';
            }
            $customer->save();

            // 6. Settle open invoices if present
            try {
                if (class_exists(TenantCustomerInvoice::class)) {
                    $openInvoices = TenantCustomerInvoice::where('tenant_id', $tenantId)
                        ->where('customer_id', $customer->id)
                        ->whereIn('status', ['unpaid', 'partially_paid', 'due', 'overdue'])
                        ->orderBy('due_date', 'asc')
                        ->get();

                    $remainingRelief = $totalRelief;
                    foreach ($openInvoices as $inv) {
                        if ($remainingRelief <= 0) break;
                        $invPayable = max(0, (float)$inv->total_payable - (float)$inv->paid_amount);
                        if ($remainingRelief >= $invPayable) {
                            $inv->paid_amount = $inv->total_payable;
                            $inv->due_amount = 0.00;
                            $inv->status = 'paid';
                            $remainingRelief -= $invPayable;
                        } else {
                            $inv->paid_amount = (float)$inv->paid_amount + $remainingRelief;
                            $inv->due_amount = max(0, (float)$inv->total_payable - (float)$inv->paid_amount);
                            $inv->status = 'partially_paid';
                            $remainingRelief = 0;
                        }
                        $inv->paid_at = now();
                        $inv->payment_method = $method;
                        $inv->save();
                    }
                }
            } catch (\Throwable $invEx) {
                Log::warning("Invoice sync error: " . $invEx->getMessage());
            }

            DB::commit();

            // 7. MikroTik RouterOS & FreeRADIUS Live Re-Activation
            if ($shouldReactivate) {
                try {
                    if (class_exists(RadiusService::class)) {
                        $radiusService = new RadiusService();
                        $radiusService->syncCustomerSubscriber($customer);
                    }
                } catch (\Throwable $rEx) {
                    Log::warning("Radius activation skipped: " . $rEx->getMessage());
                }

                try {
                    if (class_exists(MikrotikApiService::class) && $customer->router_id) {
                        $mikrotikApi = new MikrotikApiService();
                        $mikrotikApi->syncCustomerSecret($customer);
                        $mikrotikApi->enableCustomerSecret($customer);
                        $mikrotikApi->kickCustomerSession($customer);
                    }
                } catch (\Throwable $mEx) {
                    Log::warning("MikroTik activation skipped: " . $mEx->getMessage());
                }
            }

            // 8. SMS Confirmation Dispatch
            if ($shouldSendSms && !empty($customer->phone)) {
                try {
                    $companyName = $tenant->company_name ?? $tenant->name ?? 'ISP Network';
                    $expiryStr = $customer->expiry_date ? Carbon::parse($customer->expiry_date)->format('d M Y') : 'N/A';
                    $smsMsg = "Dear {$customer->name}, Tk " . number_format($amount, 2) . " received for bill. Rem Due: Tk " . number_format($customer->due_amount, 2) . ". Expiry: {$expiryStr}. Thank you, {$companyName}";
                    
                    if (class_exists(SmsService::class)) {
                        SmsService::send($customer->phone, $smsMsg);
                    }
                } catch (\Throwable $smsEx) {
                    Log::warning("SMS dispatch failed: " . $smsEx->getMessage());
                }
            }

            $successMsg = "Payment of Tk " . number_format($amount, 2) . " collected successfully for {$customer->username}!";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'payment' => $payment,
                    'customer' => $customer,
                    'receipt_url' => route('tenant.collector.receipt', $payment->id),
                ]);
            }

            return redirect()->route('tenant.collector.collect-page', $customer->id)
                ->with('success', $successMsg)
                ->with('payment_success_id', $payment->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Bill collection error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process payment: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to process payment: ' . $e->getMessage());
        }
    }
}
