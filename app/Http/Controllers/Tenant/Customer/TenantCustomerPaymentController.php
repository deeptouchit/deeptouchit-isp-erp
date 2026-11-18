<?php

namespace App\Http\Controllers\Tenant\Customer;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\TenantRouter;
use App\Services\Audit\FinancialAuditService;
use App\Services\Billing\CustomerPaymentPolicy;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantCustomerPaymentController extends Controller
{
    /**
     * Resolve the active Tenant.
     */
    protected function getTenant(): Tenant
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }
        if (!$tenant) {
            abort(404, __('ISP Tenant record not found.'));
        }
        return $tenant;
    }

    /**
     * Process Customer Bill Payment / Advance Recharge.
     */
    public function receivePayment(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $currency = $tenant->currency_symbol ?? '৳';
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'payment_mode' => 'required|in:due,advance,custom',
            'amount' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'months' => 'nullable|integer|min:1|max:36',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank_transfer,pos,other,online',
            'transaction_id' => 'nullable|string|max:100',
            'request_key' => 'required|string|max:100',
            'billing_month' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:255',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
        ]);

        $mode = $validated['payment_mode'];
        $amountPaid = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0);
        $months = (int) ($validated['months'] ?? 1);
        $method = $validated['payment_method'];
        $trxId = trim($validated['transaction_id'] ?? '');
        $requestKey = trim($validated['request_key'] ?? '');
        $billingMonth = $validated['billing_month'] ?: Carbon::now()->format('F Y');
        $notes = trim($validated['notes'] ?? '');
        $shouldExtendValidity = $request->boolean('extend_validity', true);
        $shouldReactivate = $request->boolean('reactivate_line', true);

        return DB::transaction(function () use (
            $tenant, $currency, $customer, $mode, $amountPaid, $discount,
            $months, $method, $trxId, $billingMonth, $notes, 
            $shouldExtendValidity, $shouldReactivate, $request, $requestKey
        ) {
            Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $customer = TenantCustomer::where('tenant_id', $tenant->id)
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($requestKey !== '') {
                $existingPayment = TenantCustomerPayment::where('tenant_id', $tenant->id)
                    ->where('request_key', $requestKey)
                    ->first();

                if ($existingPayment) {
                    $samePayment = (int) $existingPayment->customer_id === (int) $customer->id
                        && round((float) $existingPayment->amount, 2) === round($amountPaid, 2)
                        && round((float) $existingPayment->discount, 2) === round($discount, 2)
                        && (string) $existingPayment->payment_method === $method;

                    if (!$samePayment) {
                        return response()->json([
                            'success' => false,
                            'message' => __('This payment request key was already used for a different transaction.'),
                        ], 409);
                    }

                    return response()->json([
                        'success' => true,
                        'already_processed' => true,
                        'message' => __('This payment was already processed as receipt :invoice.', [
                            'invoice' => $existingPayment->invoice_no,
                        ]),
                        'payment' => [
                            'id' => $existingPayment->id,
                            'invoice_no' => $existingPayment->invoice_no,
                        ],
                    ]);
                }
            }

            $oldDue = (float) ($customer->due_amount ?? 0);
            $oldExpiry = $customer->expiry_date;
            $monthlyBill = (float) ($customer->monthly_bill > 0
                ? $customer->monthly_bill
                : ($customer->package?->price ?? 0));
            $policy = app(CustomerPaymentPolicy::class)->calculate(
                oldDue: $oldDue,
                oldExpiry: $oldExpiry ? Carbon::parse($oldExpiry) : null,
                monthlyBill: $monthlyBill,
                mode: $mode,
                amountPaid: $amountPaid,
                discount: $discount,
                months: $months,
                extendValidity: $shouldExtendValidity,
                reactivateRequested: $shouldReactivate,
            );
            $newDue = $policy['new_due'];
            $newExpiry = $policy['new_expiry'];

            // 3. Update Customer Record
            $customer->due_amount = $newDue;
            if ($newExpiry) {
                $customer->expiry_date = $newExpiry;
            }

            $isReactivated = false;
            if ($policy['can_reactivate']) {
                $customer->status = 'active';
                $isReactivated = true;
            }

            $customer->save();

            // 4. Generate Serialized Money Receipt Number
            $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);

            // 5. Create Payment Record
            $payment = TenantCustomerPayment::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo,
                'billing_month' => $billingMonth,
                'amount' => $amountPaid,
                'discount' => $discount,
                'payment_method' => $method,
                'collected_by' => Auth::id() ?: 1,
                'status' => 'completed',
                'paid_at' => Carbon::now(),
                'transaction_id' => $trxId ?: ('TRX-' . strtoupper(substr(md5(uniqid()), 0, 10))),
                'request_key' => $requestKey !== '' ? $requestKey : null,
                'notes' => $notes ?: ($mode === 'advance'
                    ? ($months === 1
                        ? __('Advance recharge for :count month', ['count' => $months])
                        : __('Advance recharge for :count months', ['count' => $months]))
                    : __('Bill payment for :month', ['month' => $billingMonth])),
            ]);

            // 5.1 Allocate cash and discount across oldest outstanding invoices.
            $remainingCash = $amountPaid;
            $remainingDiscount = $discount;
            $firstInvoiceId = null;
            $openInvoices = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial', 'partially_paid', 'overdue'])
                ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($openInvoices as $invoice) {
                if ($remainingCash <= 0 && $remainingDiscount <= 0) {
                    break;
                }

                $currentDue = max(0, (float) $invoice->due_amount);
                if ($currentDue <= 0) {
                    continue;
                }

                $cashApplied = min($remainingCash, $currentDue);
                $discountApplied = min($remainingDiscount, $currentDue - $cashApplied);
                $coverage = round($cashApplied + $discountApplied, 2);
                if ($coverage <= 0) {
                    continue;
                }

                $invoice->paid_amount = round((float) $invoice->paid_amount + $cashApplied, 2);
                $invoice->discount = round((float) $invoice->discount + $discountApplied, 2);
                $invoice->due_amount = max(0, round($currentDue - $coverage, 2));
                $invoice->status = $invoice->due_amount <= 0 ? 'paid' : 'partially_paid';
                $invoice->paid_at = $invoice->due_amount <= 0 ? Carbon::now() : null;
                $invoice->payment_method = $method;
                $invoice->save();

                DB::table('tenant_customer_payment_allocations')->insert([
                    'tenant_id' => $tenant->id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $cashApplied,
                    'discount' => $discountApplied,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $firstInvoiceId ??= $invoice->id;
                $remainingCash = round($remainingCash - $cashApplied, 2);
                $remainingDiscount = round($remainingDiscount - $discountApplied, 2);
            }

            if ($firstInvoiceId !== null) {
                $payment->invoice_id = $firstInvoiceId;
                $payment->save();
            }

            // 5.2 Record in Central Online Gateway Transactions if paid via PGW
            if (in_array(strtolower($method), ['bkash', 'nagad', 'rocket', 'sslcommerz', 'shurjopay', 'upay', 'aamarpay', 'stripe', 'online', 'pos'])) {
                try {
                    $gwSlug = strtolower(str_replace(['_merchant', '_pgw'], '', $method));
                    if ($gwSlug === 'online' || $gwSlug === 'pos') {
                        $gwSlug = 'sslcommerz';
                    }

                    \App\Models\TenantGatewayTransaction::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'transaction_id' => 'PGW-CUST-' . $payment->id,
                        ],
                        [
                            'gateway_trx_id' => $trxId ?: $payment->transaction_id,
                            'gateway' => $gwSlug,
                            'purpose' => $mode === 'advance' ? 'CUSTOMER_RECHARGE' : 'CUSTOMER_BILL',
                            'customer_id' => $customer->id,
                            'reseller_id' => $customer->reseller_id,
                            'user_id' => Auth::id() ?: 1,
                            'reference_id' => $payment->invoice_no,
                            'amount' => $amountPaid,
                            'fee_amount' => 0.00,
                            'net_amount' => $amountPaid,
                            'currency' => $tenant->currency_code ?? 'BDT',
                            'payer_account' => $customer->phone,
                            'payer_name' => $customer->name,
                            'status' => 'SUCCESS',
                            'status_message' => __('Cleared from Customer Collections'),
                            'ip_address' => $request->ip(),
                            'initiated_at' => Carbon::now(),
                            'completed_at' => Carbon::now(),
                        ]
                    );
                } catch (\Exception $e) {
                    // Non-blocking gateway log sync
                }
            }

            // 6. Sync to FreeRADIUS AAA & MikroTik RouterOS
            $radiusSynced = false;
            $mikrotikSynced = false;
            try {
                $radiusService = new RadiusService();
                $radiusRes = $radiusService->syncCustomerSubscriber($customer);
                if (!empty($radiusRes['success'])) {
                    $radiusSynced = true;
                }
            } catch (\Exception $e) {
                \Log::warning('Customer payment RADIUS provisioning requires retry.', [
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'retry_recommended' => true,
                ]);
            }

            try {
                $mikrotikApi = new MikrotikApiService();
                $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($syncRes['success'])) {
                    $mikrotikSynced = true;
                }
            } catch (\Exception $e) {
                \Log::warning('Customer payment MikroTik provisioning requires retry.', [
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'payment_id' => $payment->id,
                    'retry_recommended' => true,
                ]);
            }

            // 7. Audit Trail Log
            $logParams = [
                'currency' => $currency,
                'amount' => number_format($amountPaid, 2),
                'invoice' => $invoiceNo,
                'name' => $customer->name,
                'username' => $customer->username,
                'method' => $payment->payment_method_name,
            ];
            $logDescription = $isReactivated
                ? __('Received payment of :currency:amount (Invoice: :invoice) from subscriber :name (:username) via :method. Line re-activated.', $logParams)
                : __('Received payment of :currency:amount (Invoice: :invoice) from subscriber :name (:username) via :method.', $logParams);

            app(FinancialAuditService::class)->record(
                $tenant,
                'CUSTOMER_PAYMENT_COLLECTED',
                $logDescription,
                [
                    'payment_id' => $payment->id,
                    'invoice_no' => $invoiceNo,
                    'customer_id' => $customer->id,
                    'amount' => $amountPaid,
                    'discount' => $discount,
                    'old_due' => $oldDue,
                    'new_due' => $newDue,
                    'old_expiry' => $oldExpiry ? Carbon::parse($oldExpiry)->format('Y-m-d') : null,
                    'new_expiry' => $newExpiry ? Carbon::parse($newExpiry)->format('Y-m-d') : null,
                    'is_reactivated' => $isReactivated,
                    'network_provisioning' => [
                        'radius' => $radiusSynced ? 'success' : 'pending_retry',
                        'mikrotik' => $mikrotikSynced ? 'success' : 'pending_retry',
                    ],
                ],
                $request,
            );

            $remainingDays = 0;
            if ($customer->expiry_date) {
                $today = Carbon::today();
                $remainingDays = $customer->expiry_date->isPast() ? 0 : (int) $today->diffInDays($customer->expiry_date, false);
            }

            $msgParams = [
                'currency' => $currency,
                'amount' => number_format($amountPaid, 2),
                'invoice' => $invoiceNo,
            ];
            if ($radiusSynced && $mikrotikSynced) {
                $responseMessage = __('Payment of :currency:amount received successfully! (Receipt: :invoice) FreeRADIUS active & MikroTik unblocked.', $msgParams);
            } elseif ($radiusSynced) {
                $responseMessage = __('Payment of :currency:amount received successfully! (Receipt: :invoice) FreeRADIUS active.', $msgParams);
            } elseif ($mikrotikSynced) {
                $responseMessage = __('Payment of :currency:amount received successfully! (Receipt: :invoice) MikroTik unblocked.', $msgParams);
            } else {
                $responseMessage = __('Payment of :currency:amount received successfully! (Receipt: :invoice)', $msgParams);
            }

            return response()->json([
                'success' => true,
                'message' => $responseMessage,
                'payment' => [
                    'id' => $payment->id,
                    'invoice_no' => $payment->invoice_no,
                    'amount' => number_format($payment->amount, 2),
                    'discount' => number_format($payment->discount, 2),
                    'total' => number_format($payment->amount + $payment->discount, 2),
                    'method' => $payment->payment_method_name,
                    'paid_at' => $payment->paid_at->translatedFormat('d M Y, h:i A'),
                    'transaction_id' => $payment->transaction_id,
                    'billing_month' => $payment->billing_month,
                    'collector_name' => Auth::user()?->name ?: __('Admin'),
                    'notes' => $payment->notes,
                ],
                'customer' => [
                    'id' => $customer->id,
                    'due_amount' => number_format($customer->due_amount, 2),
                    'due_amount_raw' => (float) $customer->due_amount,
                    'expiry_date' => $customer->expiry_date ? $customer->expiry_date->translatedFormat('d M Y') : '--',
                    'expiry_raw' => $customer->expiry_date ? $customer->expiry_date->format('Y-m-d') : '',
                    'remaining_days' => $remainingDays,
                    'status' => $customer->status,
                    'effective_status' => $customer->effective_status,
                    'status_badge' => $customer->status_badge,
                ],
            ]);
        });
    }

    /**
     * Dedicated Bulk Payments Page (Reseller-wise & Custom/Direct Retail).
     */
    public function bulkPaymentPage(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $mode = $request->get('mode', 'reseller'); // 'reseller' or 'direct'
        $resellerId = $request->get('reseller_id');
        $zone = $request->get('zone');
        $packageId = $request->get('package_id');
        $status = $request->get('status', 'all_due'); // 'all_due', 'all', 'active', 'expired', 'suspended', 'due'
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', '50');

        $resellers = TenantReseller::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        // If mode is reseller and no reseller_id is explicitly selected, default to the first active reseller if available
        if ($mode === 'reseller' && empty($resellerId) && $resellers->isNotEmpty() && !$request->has('reseller_id')) {
            $resellerId = $resellers->first()->id;
        }

        $selectedReseller = null;
        if (!empty($resellerId)) {
            $selectedReseller = $resellers->firstWhere('id', (int)$resellerId);
        }

        $zones = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereNotNull('zone')
            ->where('zone', '!=', '')
            ->distinct()
            ->orderBy('zone')
            ->pluck('zone');

        $packages = TenantInternetPackage::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('package_name')
            ->get();

        $query = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['package', 'reseller']);

        // Scope Mode Filter
        if ($mode === 'reseller') {
            if ($selectedReseller) {
                $query->where('reseller_id', $selectedReseller->id);
            } else {
                $query->whereNotNull('reseller_id');
            }
        } elseif ($mode === 'direct') {
            $query->whereNull('reseller_id');
        }

        // Additional Specific Filters
        if ($zone) {
            $query->where('zone', $zone);
        }
        if ($packageId) {
            $query->where('package_id', $packageId);
        }

        // Status Filter
        if ($status === 'all_due') {
            $query->where(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->orWhereIn('status', ['due', 'expired', 'suspended']);
            });
        } elseif ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // KPIs calculation before pagination
        $allMatchingSubscribers = (clone $query)->get();
        $totalSubscribersCount = $allMatchingSubscribers->count();
        $totalGrossBill = (float) $allMatchingSubscribers->sum(function ($c) {
            return (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
        });

        $commissionRate = (float) ($selectedReseller?->commission_rate ?? 0);
        if ($mode === 'reseller' && $selectedReseller) {
            $totalResellerCommission = ($totalGrossBill * $commissionRate) / 100;
            $totalAdminShare = max(0, $totalGrossBill - $totalResellerCommission);
        } else {
            $totalResellerCommission = 0.00;
            $totalAdminShare = $totalGrossBill;
        }

        $resellerWalletBalance = (float) ($selectedReseller?->wallet_balance ?? 0);
        $resellerCreditLimit = (float) ($selectedReseller?->credit_limit ?? 0);
        $resellerAvailableBalance = (float) ($selectedReseller?->total_available_balance ?? 0);

        if ($perPage === 'all') {
            $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->paginate(500)->withQueryString();
        } else {
            $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->paginate((int)$perPage)->withQueryString();
        }

        $kpis = [
            'total_subscribers' => $totalSubscribersCount,
            'gross_bill' => $totalGrossBill,
            'admin_share' => $totalAdminShare,
            'reseller_commission' => $totalResellerCommission,
            'commission_rate' => $commissionRate,
            'reseller_wallet' => $resellerWalletBalance,
            'reseller_credit' => $resellerCreditLimit,
            'reseller_available' => $resellerAvailableBalance,
            'mode' => $mode,
        ];

        return view('tenant.customers.bulk_payments', compact(
            'tenant',
            'currencySymbol',
            'resellers',
            'selectedReseller',
            'zones',
            'packages',
            'subscribers',
            'kpis',
            'mode',
            'resellerId',
            'zone',
            'packageId',
            'status',
            'search',
            'perPage'
        ));
    }

    /**
     * Printable Sheet for Bulk Bill & Due Payments
     */
    public function printBulkPayments(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $mode = $request->get('mode', 'reseller');
        $resellerId = $request->get('reseller_id');
        $zone = $request->get('zone');
        $packageId = $request->get('package_id');
        $status = $request->get('status', 'all_due');
        $search = trim($request->get('search', ''));
        $selectedIds = $request->get('ids');

        $reseller = null;
        if (!empty($resellerId)) {
            $reseller = TenantReseller::where('tenant_id', $tenant->id)->find((int)$resellerId);
        }

        $query = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['package', 'reseller']);

        if (!empty($selectedIds)) {
            $idArray = is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds);
            $cleanIds = array_filter(array_map('intval', $idArray));
            if (!empty($cleanIds)) {
                $query->whereIn('id', $cleanIds);
                $orderRaw = implode(',', $cleanIds);
                $subscribers = $query->orderByRaw("FIELD(id, {$orderRaw})")->get();
            } else {
                $subscribers = collect();
            }
        } else {
            if ($mode === 'reseller') {
                if ($reseller) {
                    $query->where('reseller_id', $reseller->id);
                } else {
                    $query->whereNotNull('reseller_id');
                }
            } elseif ($mode === 'direct') {
                $query->whereNull('reseller_id');
            }

            if ($zone) {
                $query->where('zone', $zone);
            }
            if ($packageId) {
                $query->where('package_id', $packageId);
            }

            if ($status === 'all_due') {
                $query->where(function ($q) {
                    $q->where('due_amount', '>', 0)
                      ->orWhereIn('status', ['due', 'expired', 'suspended']);
                });
            } elseif ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('customer_id', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page');
            if ($perPage && $perPage !== 'all') {
                $query->limit((int)$perPage);
            }

            $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->get();
        }

        $totalGrossBill = (float) $subscribers->sum(function ($c) {
            return (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
        });

        $commissionRate = (float) ($reseller?->commission_rate ?? 0);
        $totalResellerCommission = ($totalGrossBill * $commissionRate) / 100;
        $totalAdminShare = max(0, $totalGrossBill - $totalResellerCommission);

        return view('tenant.customers.bulk_payments_print', compact(
            'tenant',
            'reseller',
            'currencySymbol',
            'subscribers',
            'totalGrossBill',
            'totalResellerCommission',
            'totalAdminShare',
            'commissionRate',
            'mode',
            'status',
            'zone'
        ));
    }

    /**
     * Process Bulk Payment for Reseller or Direct Retail.
     */
    public function processBulkPayment(Request $request): JsonResponse
    {
        $tenant = $this->getTenant();
        $currency = $tenant->currency_symbol ?? '৳';

        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'required|integer',
            'payment_method' => 'required|string|in:reseller_wallet,cash,bkash,nagad,rocket,bank_transfer,pos,other,online',
            'billing_month' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:255',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
            'reseller_id' => 'nullable|integer',
            'request_key' => 'required|string|max:60',
        ]);

        $customerIds = array_values(array_unique(array_map('intval', $validated['customer_ids'])));
        $method = $validated['payment_method'];
        $billingMonth = $validated['billing_month'] ?: Carbon::now()->format('F Y');
        $notes = trim($validated['notes'] ?? '');
        $shouldExtendValidity = $request->boolean('extend_validity', true);
        $shouldReactivate = $request->boolean('reactivate_line', true);
        $resellerId = isset($validated['reseller_id']) ? (int) $validated['reseller_id'] : null;
        $batchKey = trim($validated['request_key']);

        $result = DB::transaction(function () use (
            $tenant, $currency, $customerIds, $method, $billingMonth, $notes,
            $shouldExtendValidity, $shouldReactivate, $resellerId, $request, $batchKey
        ): array {
            Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $customers = TenantCustomer::where('tenant_id', $tenant->id)
                ->whereIn('id', $customerIds)
                ->with(['package', 'reseller'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            abort_unless(
                $customers->count() === count($customerIds),
                422,
                __('One or more selected customers do not belong to this tenant.')
            );

            $resellerKeys = $customers
                ->map(fn (TenantCustomer $customer): string => $customer->reseller_id ? (string) $customer->reseller_id : 'direct')
                ->unique()
                ->values();
            abort_if($resellerKeys->count() > 1, 422, __('A bulk payment cannot mix customers from different resellers or direct retail.'));

            $resolvedResellerId = $resellerKeys->first() !== 'direct' ? (int) $resellerKeys->first() : null;
            if ($resellerId !== null) {
                abort_unless($resolvedResellerId === $resellerId, 422, __('Selected customers do not belong to the selected reseller.'));
            }

            $reseller = null;
            if ($resolvedResellerId !== null) {
                $resellerQuery = TenantReseller::where('tenant_id', $tenant->id)->whereKey($resolvedResellerId);
                if ($method === 'reseller_wallet') {
                    $resellerQuery->lockForUpdate();
                }
                $reseller = $resellerQuery->firstOrFail();
            }
            abort_if($method === 'reseller_wallet' && !$reseller, 422, __('Reseller Wallet payment requires one valid reseller.'));

            $payableCustomers = $customers->filter(function (TenantCustomer $customer): bool {
                $due = max(0, (float) $customer->due_amount);
                $monthlyBill = max(0, (float) ($customer->monthly_bill ?: ($customer->package?->price ?? 0)));
                return $due > 0 || $monthlyBill > 0;
            })->values();
            abort_if($payableCustomers->isEmpty(), 422, __('No selected customer has a payable due or monthly bill.'));

            $paymentKeys = $payableCustomers->mapWithKeys(fn (TenantCustomer $customer): array => [
                $customer->id => $batchKey . ':' . $customer->id,
            ]);
            $existingPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->whereIn('request_key', $paymentKeys->values())
                ->get();
            if ($existingPayments->isNotEmpty()) {
                abort_unless($existingPayments->count() === $payableCustomers->count(), 409, __('This bulk request was only partially recorded and requires review.'));
                foreach ($existingPayments as $existingPayment) {
                    abort_unless(
                        $paymentKeys->get($existingPayment->customer_id) === $existingPayment->request_key
                            && $existingPayment->payment_method === $method,
                        409,
                        __('This bulk request key was already used for a different transaction.')
                    );
                }

                $replayGross = (float) $existingPayments->sum('amount');
                $replayCommission = $reseller ? ($replayGross * (float) $reseller->commission_rate) / 100 : 0.0;
                return [
                    'already_processed' => true,
                    'processed_count' => $existingPayments->count(),
                    'gross_bill' => $replayGross,
                    'admin_share' => max(0, $replayGross - $replayCommission),
                    'reseller_commission' => $replayCommission,
                    'reseller_wallet_balance' => $reseller?->wallet_balance,
                    'reactivated_ids' => [],
                ];
            }

            $totalGrossBill = (float) $payableCustomers->sum(function (TenantCustomer $customer): float {
                $due = max(0, (float) $customer->due_amount);
                $monthlyBill = max(0, (float) ($customer->monthly_bill ?: ($customer->package?->price ?? 0)));
                return $due > 0 ? $due : $monthlyBill;
            });

            $commissionRate = (float) ($reseller?->commission_rate ?? 0);
            $resellerCommission = ($totalGrossBill * $commissionRate) / 100;
            $adminShare = max(0, $totalGrossBill - $resellerCommission);

            if ($method === 'reseller_wallet') {
                $availableBalance = (float) $reseller->total_available_balance;
                abort_if(
                    $availableBalance < $adminShare,
                    422,
                    __('Insufficient Reseller Wallet Balance! Available: :currency:available, Required (Admin Share): :currency:required', [
                            'currency' => $currency,
                            'available' => number_format($availableBalance, 2),
                            'required' => number_format($adminShare, 2),
                    ])
                );

                $balanceBefore = (float) $reseller->wallet_balance;
                $reseller->wallet_balance = round($balanceBefore - $adminShare, 2);
                $reseller->save();

                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => 'TXN-' . strtoupper(Str::random(10)),
                    'type' => 'DEBIT',
                    'amount' => $adminShare,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $reseller->wallet_balance,
                    'payment_method' => 'reseller_wallet',
                    'reference_no' => $batchKey,
                    'description' => __('Bulk payment for :count subscribers (:month). Gross: :currency:gross, Reseller Profit: :currency:profit, Admin Share Deducted: :currency:admin', [
                        'count' => $payableCustomers->count(),
                        'month' => $billingMonth,
                        'currency' => $currency,
                        'gross' => number_format($totalGrossBill, 2),
                        'profit' => number_format($resellerCommission, 2),
                        'admin' => number_format($adminShare, 2),
                    ]),
                    'created_by' => Auth::id() ?: 1,
                ]);
            }

            $processedCount = 0;
            $totalAmountCollected = 0.00;
            $reactivatedIds = [];

            foreach ($payableCustomers as $customer) {
                $oldDue = (float) ($customer->due_amount ?? 0);
                $monthlyBill = (float) ($customer->monthly_bill > 0 ? $customer->monthly_bill : ($customer->package?->price ?? 0));
                $amountToPay = $oldDue > 0 ? $oldDue : $monthlyBill;
                abort_if($amountToPay <= 0, 422, __('A zero-value customer cannot be processed in bulk payment.'));

                $policy = app(CustomerPaymentPolicy::class)->calculate(
                    oldDue: $oldDue,
                    oldExpiry: $customer->expiry_date ? Carbon::parse($customer->expiry_date) : null,
                    monthlyBill: $monthlyBill,
                    mode: $oldDue > 0 ? 'due' : 'advance',
                    amountPaid: $amountToPay,
                    discount: 0,
                    months: 1,
                    extendValidity: $shouldExtendValidity,
                    reactivateRequested: $shouldReactivate,
                );
                $customer->due_amount = $policy['new_due'];
                if ($policy['new_expiry']) {
                    $customer->expiry_date = $policy['new_expiry'];
                }
                if ($policy['can_reactivate']) {
                    $customer->status = 'active';
                    $reactivatedIds[] = $customer->id;
                }
                $customer->save();

                $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);
                $payment = TenantCustomerPayment::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'invoice_no' => $invoiceNo,
                    'billing_month' => $billingMonth,
                    'amount' => $amountToPay,
                    'discount' => 0.00,
                    'payment_method' => $method,
                    'collected_by' => Auth::id() ?: 1,
                    'status' => 'completed',
                    'paid_at' => Carbon::now(),
                    'transaction_id' => 'BULK-' . strtoupper(substr(md5(uniqid() . $customer->id), 0, 8)),
                    'request_key' => $paymentKeys->get($customer->id),
                    'notes' => $notes ?: ($reseller
                        ? __('Bulk payment clearance for :month (Reseller: :reseller)', ['month' => $billingMonth, 'reseller' => $reseller->name])
                        : __('Bulk payment clearance for :month', ['month' => $billingMonth])),
                ]);

                $remaining = $amountToPay;
                $firstInvoiceId = null;
                $openInvoices = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', ['unpaid', 'partial', 'partially_paid', 'overdue'])
                    ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
                foreach ($openInvoices as $invoice) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $currentDue = max(0, (float) $invoice->due_amount);
                    $applied = min($remaining, $currentDue);
                    if ($applied <= 0) {
                        continue;
                    }
                    $invoice->paid_amount = round((float) $invoice->paid_amount + $applied, 2);
                    $invoice->due_amount = max(0, round($currentDue - $applied, 2));
                    $invoice->status = $invoice->due_amount <= 0 ? 'paid' : 'partially_paid';
                    $invoice->paid_at = $invoice->due_amount <= 0 ? Carbon::now() : null;
                    $invoice->payment_method = $method;
                    $invoice->save();
                    DB::table('tenant_customer_payment_allocations')->insert([
                        'tenant_id' => $tenant->id,
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $applied,
                        'discount' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $firstInvoiceId ??= $invoice->id;
                    $remaining = round($remaining - $applied, 2);
                }
                if ($firstInvoiceId !== null) {
                    $payment->invoice_id = $firstInvoiceId;
                    $payment->save();
                }

                $processedCount++;
                $totalAmountCollected += $amountToPay;
            }

            // Audit Trail Log
            app(FinancialAuditService::class)->record(
                $tenant,
                'CUSTOMER_BULK_PAYMENT_COLLECTED',
                __('Bulk payment processed for :count subscribers. Total Gross: :currency:gross, Admin Share: :currency:admin, Reseller Share: :currency:profit via :method.', [
                    'count' => $processedCount,
                    'currency' => $currency,
                    'gross' => number_format($totalAmountCollected, 2),
                    'admin' => number_format($adminShare, 2),
                    'profit' => number_format($resellerCommission, 2),
                    'method' => $method,
                ]),
                [
                    'processed_count' => $processedCount,
                    'gross_bill' => $totalAmountCollected,
                    'admin_share' => $adminShare,
                    'reseller_commission' => $resellerCommission,
                    'reseller_id' => $reseller?->id,
                    'reseller_name' => $reseller?->name,
                    'customer_ids' => $customerIds,
                    'method' => $method,
                    'billing_month' => $billingMonth,
                    'request_key' => $batchKey,
                ],
                $request,
            );

            return [
                'already_processed' => false,
                'processed_count' => $processedCount,
                'gross_bill' => $totalAmountCollected,
                'admin_share' => $adminShare,
                'reseller_commission' => $resellerCommission,
                'reseller_wallet_balance' => $reseller?->wallet_balance,
                'reactivated_ids' => $reactivatedIds,
            ];
        });

        if (!$result['already_processed'] && !empty($result['reactivated_ids'])) {
            $reactivatedCustomers = TenantCustomer::where('tenant_id', $tenant->id)
                ->whereIn('id', $result['reactivated_ids'])->get();
            foreach ($reactivatedCustomers as $customer) {
                try {
                    (new RadiusService())->syncCustomerSubscriber($customer);
                } catch (\Throwable $e) {
                    \Log::warning('Bulk payment RADIUS sync failed.', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
                }
                try {
                    (new MikrotikApiService())->syncCustomerToMikrotik($customer);
                } catch (\Throwable $e) {
                    \Log::warning('Bulk payment MikroTik sync failed.', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'already_processed' => $result['already_processed'],
            'processed_count' => $result['processed_count'],
            'gross_bill' => number_format($result['gross_bill'], 2),
            'admin_share' => number_format($result['admin_share'], 2),
            'reseller_commission' => number_format($result['reseller_commission'], 2),
            'reseller_wallet_balance' => $result['reseller_wallet_balance'] !== null
                ? number_format((float) $result['reseller_wallet_balance'], 2)
                : null,
            'message' => $result['already_processed']
                ? __('This bulk payment batch was already processed.')
                : __('Bulk payment completed for :count subscribers! Total: :currency:total (Admin Share: :currency:admin)', [
                    'count' => $result['processed_count'],
                    'currency' => $currency,
                    'total' => number_format($result['gross_bill'], 2),
                    'admin' => number_format($result['admin_share'], 2),
                ]),
        ]);
    }

    /**
     * Backward-compatible alias for bulkReceivePayment.
     */
    public function bulkReceivePayment(Request $request): JsonResponse
    {
        return $this->processBulkPayment($request);
    }

    /**
     * Render Printable Money Receipt.
     */
    public function printReceipt(Request $request, int $id): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer.package', 'collector'])
            ->findOrFail($id);

        return view('tenant.customers.receipt', compact('tenant', 'payment', 'currencySymbol'));
    }

    /**
     * List Customer Payments (JSON).
     */
    public function history(Request $request, int $customerId): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($customerId);

        $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->with('collector')
            ->latest('paid_at')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'payments' => $payments,
        ]);
    }
}
