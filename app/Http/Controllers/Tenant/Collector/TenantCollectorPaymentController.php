<?php

namespace App\Http\Controllers\Tenant\Collector;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank',
            'trx_id' => 'nullable|string|max:100',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $customer = TenantCustomer::where('tenant_id', $tenantId)
            ->where('id', $request->customer_id)
            ->firstOrFail();

        $amount = (float) $request->amount;
        $discount = (float) ($request->discount ?? 0);
        $method = $request->payment_method;

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = TenantCustomerPayment::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'reseller_id' => null, // ISP Core
                'amount' => $amount,
                'discount' => $discount,
                'payment_method' => $method,
                'transaction_id' => $request->trx_id,
                'collected_by' => $user->id,
                'paid_at' => now(),
                'notes' => $request->notes ?: "Field collection by {$user->name}",
                'status' => 'approved',
            ]);

            // Adjust Customer Balance / Due
            $totalRelief = $amount + $discount;
            $customer->due_amount = max(0, (float)$customer->due_amount - $totalRelief);
            
            // If due cleared or active bill paid, mark status active
            if ($customer->status === 'due' || $customer->status === 'expired') {
                $customer->status = 'active';
            }
            $customer->save();

            DB::commit();

            $msg = "পেমেন্ট সফলভাবে সম্পন্ন হয়েছে! গ্রাহক '{$customer->username}' এর একাউন্টে " . number_format($amount, 2) . " ৳ জমা হয়েছে।";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'payment' => $payment,
                    'customer' => $customer,
                ]);
            }

            return back()->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'বিল গ্রহণে সমস্যা হয়েছে: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'বিল গ্রহণে সমস্যা হয়েছে: ' . $e->getMessage());
        }
    }
}
