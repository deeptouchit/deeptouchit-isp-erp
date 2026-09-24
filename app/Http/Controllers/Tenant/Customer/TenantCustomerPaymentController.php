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
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Process Customer Bill Payment / Advance Recharge.
     */
    public function receivePayment(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $customer = TenantCustomer::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'payment_mode' => 'required|in:due,advance,custom',
            'amount' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'months' => 'nullable|integer|min:1|max:36',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank_transfer,pos,other,online',
            'transaction_id' => 'nullable|string|max:100',
            'billing_month' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:255',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
        ]);

        $mode = $validated['payment_mode'];
        $amountPaid = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0);
        $totalCovered = $amountPaid + $discount;
        $months = (int) ($validated['months'] ?? 1);
        $method = $validated['payment_method'];
        $trxId = trim($validated['transaction_id'] ?? '');
        $billingMonth = $validated['billing_month'] ?: Carbon::now()->format('F Y');
        $notes = trim($validated['notes'] ?? '');
        $shouldExtendValidity = $request->boolean('extend_validity', true);
        $shouldReactivate = $request->boolean('reactivate_line', true);

        return DB::transaction(function () use (
            $tenant, $customer, $mode, $amountPaid, $discount, $totalCovered, 
            $months, $method, $trxId, $billingMonth, $notes, 
            $shouldExtendValidity, $shouldReactivate, $request
        ) {
            $oldDue = (float) ($customer->due_amount ?? 0);
            $oldExpiry = $customer->expiry_date;
            $newDue = $oldDue;
            $newExpiry = $oldExpiry;

            // 1. Calculate Due Deduction
            if ($oldDue > 0) {
                if ($totalCovered >= $oldDue) {
                    $newDue = 0.00;
                } else {
                    $newDue = round($oldDue - $totalCovered, 2);
                }
            }

            // 2. Calculate Expiry Date Extension
            if ($shouldExtendValidity) {
                $baseExpiry = ($oldExpiry && Carbon::parse($oldExpiry)->isFuture()) 
                    ? Carbon::parse($oldExpiry) 
                    : Carbon::now();

                if ($mode === 'advance') {
                    $newExpiry = $baseExpiry->copy()->addMonths($months)->endOfDay();
                } elseif ($mode === 'due') {
                    // Regular 1-month recharge / clearance
                    $newExpiry = $baseExpiry->copy()->addMonth()->endOfDay();
                } elseif ($mode === 'custom') {
                    $monthlyBill = (float) ($customer->monthly_bill > 0 ? $customer->monthly_bill : 500);
                    $dailyRate = $monthlyBill / 30;
                    if ($dailyRate > 0) {
                        $daysToAdd = (int) floor($totalCovered / $dailyRate);
                        if ($daysToAdd > 0) {
                            $newExpiry = $baseExpiry->copy()->addDays($daysToAdd)->endOfDay();
                        }
                    }
                }
            }

            // 3. Update Customer Record
            $customer->due_amount = $newDue;
            if ($newExpiry) {
                $customer->expiry_date = $newExpiry;
            }

            $isReactivated = false;
            if ($shouldReactivate) {
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
                'notes' => $notes ?: ($mode === 'advance' ? "Advance recharge for {$months} month(s)" : "Bill payment for {$billingMonth}"),
            ]);

            // 5.1 Settle matching or open TenantCustomerInvoice
            try {
                $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                    ->orderBy('due_date', 'asc')
                    ->first();

                if ($invoice) {
                    $totalCoverage = (float)$amountPaid + (float)$discount;
                    $currentDue = $invoice->due_amount > 0 ? (float)$invoice->due_amount : ((float)$invoice->total_payable - (float)$invoice->paid_amount);

                    if ($totalCoverage >= $currentDue) {
                        $invoice->paid_amount = $invoice->total_payable;
                        $invoice->due_amount = 0.00;
                        $invoice->status = 'paid';
                    } else {
                        $invoice->paid_amount = (float)$invoice->paid_amount + $totalCoverage;
                        $invoice->due_amount = max(0, (float)$invoice->total_payable - (float)$invoice->paid_amount);
                        $invoice->status = 'partial';
                    }
                    $invoice->paid_at = Carbon::now();
                    $invoice->payment_method = $method;
                    $invoice->save();
                }
            } catch (\Exception $e) {
                // Non-blocking invoice sync
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
                            'status_message' => 'Cleared from Customer Collections',
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
            $syncMsg = '';
            try {
                $radiusService = new RadiusService();
                $radiusRes = $radiusService->syncCustomerSubscriber($customer);
                if (!empty($radiusRes['success'])) {
                    $syncMsg .= ' & FreeRADIUS active';
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            try {
                $mikrotikApi = new MikrotikApiService();
                $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
                if (!empty($syncRes['success'])) {
                    $syncMsg .= ' & MikroTik unblocked';
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            // 7. Audit Trail Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: 'Admin',
                'event_type' => 'CUSTOMER_PAYMENT_COLLECTED',
                'description' => "Received payment of ৳" . number_format($amountPaid, 2) . " (Invoice: {$invoiceNo}) from subscriber '{$customer->name}' ({$customer->username}) via {$payment->payment_method_name}." . ($isReactivated ? " Line re-activated." : ""),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'invoice_no' => $invoiceNo,
                    'customer_id' => $customer->id,
                    'amount' => $amountPaid,
                    'discount' => $discount,
                    'old_due' => $oldDue,
                    'new_due' => $newDue,
                    'new_expiry' => $newExpiry ? Carbon::parse($newExpiry)->format('Y-m-d') : null,
                    'is_reactivated' => $isReactivated,
                ],
            ]);

            $remainingDays = 0;
            if ($customer->expiry_date) {
                $today = Carbon::today();
                $remainingDays = $customer->expiry_date->isPast() ? 0 : (int) $today->diffInDays($customer->expiry_date, false);
            }

            return response()->json([
                'success' => true,
                'message' => "Payment of ৳" . number_format($amountPaid, 2) . " received successfully! (Receipt: {$invoiceNo}){$syncMsg}",
                'payment' => [
                    'id' => $payment->id,
                    'invoice_no' => $payment->invoice_no,
                    'amount' => number_format($payment->amount, 2),
                    'discount' => number_format($payment->discount, 2),
                    'total' => number_format($payment->amount + $payment->discount, 2),
                    'method' => $payment->payment_method_name,
                    'paid_at' => $payment->paid_at->format('d M Y, h:i A'),
                    'transaction_id' => $payment->transaction_id,
                    'billing_month' => $payment->billing_month,
                    'collector_name' => Auth::user()?->name ?: 'Admin',
                    'notes' => $payment->notes,
                ],
                'customer' => [
                    'id' => $customer->id,
                    'due_amount' => number_format($customer->due_amount, 2),
                    'due_amount_raw' => (float) $customer->due_amount,
                    'expiry_date' => $customer->expiry_date ? $customer->expiry_date->format('d M Y') : '--',
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
            $query->whereIn('id', array_map('intval', $idArray));
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
        }

        $subscribers = $query->orderBy('due_amount', 'desc')->orderBy('name')->get();

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

        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'required|integer',
            'payment_method' => 'required|string|in:reseller_wallet,cash,bkash,nagad,rocket,bank_transfer,pos,other,online',
            'billing_month' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:255',
            'extend_validity' => 'nullable|boolean',
            'reactivate_line' => 'nullable|boolean',
            'reseller_id' => 'nullable|integer',
        ]);

        $customerIds = $validated['customer_ids'];
        $method = $validated['payment_method'];
        $billingMonth = $validated['billing_month'] ?: Carbon::now()->format('F Y');
        $notes = trim($validated['notes'] ?? '');
        $shouldExtendValidity = $request->boolean('extend_validity', true);
        $shouldReactivate = $request->boolean('reactivate_line', true);
        $resellerId = $validated['reseller_id'] ?? null;

        return DB::transaction(function () use (
            $tenant, $customerIds, $method, $billingMonth, $notes,
            $shouldExtendValidity, $shouldReactivate, $resellerId, $request
        ) {
            $customers = TenantCustomer::where('tenant_id', $tenant->id)
                ->whereIn('id', $customerIds)
                ->with(['package', 'reseller'])
                ->get();

            if ($customers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching customers found for bulk processing.',
                ], 422);
            }

            // Calculate Gross Total Bill
            $totalGrossBill = 0.00;
            foreach ($customers as $c) {
                $dueVal = (float) ($c->due_amount > 0 ? $c->due_amount : ($c->monthly_bill ?: 0));
                $totalGrossBill += max(0, $dueVal);
            }

            // Reseller identification
            $reseller = null;
            if ($resellerId) {
                $reseller = TenantReseller::where('tenant_id', $tenant->id)->find($resellerId);
            } elseif ($customers->first()->reseller_id) {
                $reseller = TenantReseller::where('tenant_id', $tenant->id)->find($customers->first()->reseller_id);
            }

            $commissionRate = (float) ($reseller?->commission_rate ?? 0);
            $resellerCommission = ($totalGrossBill * $commissionRate) / 100;
            $adminShare = max(0, $totalGrossBill - $resellerCommission);

            // Handle Reseller Wallet Deduction
            if ($method === 'reseller_wallet') {
                if (!$reseller) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reseller Wallet payment requires an active Reseller to be selected.',
                    ], 422);
                }

                $availableBalance = (float) $reseller->total_available_balance;
                if ($availableBalance < $adminShare) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient Reseller Wallet Balance! Available: ৳" . number_format($availableBalance, 2) . ", Required (Admin Share): ৳" . number_format($adminShare, 2),
                    ], 422);
                }

                $balanceBefore = (float) $reseller->wallet_balance;
                $reseller->wallet_balance = round($balanceBefore - $adminShare, 2);
                $reseller->save();

                // Create Wallet Transaction Record
                TenantResellerWalletTransaction::create([
                    'tenant_id' => $tenant->id,
                    'reseller_id' => $reseller->id,
                    'trx_id' => 'TXN-' . strtoupper(Str::random(10)),
                    'type' => 'DEBIT',
                    'amount' => $adminShare,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $reseller->wallet_balance,
                    'payment_method' => 'reseller_wallet',
                    'reference_no' => 'BULK-' . count($customers) . '-' . date('YmdHis'),
                    'description' => "Bulk payment for " . count($customers) . " subscribers ({$billingMonth}). Gross: ৳" . number_format($totalGrossBill, 2) . ", Reseller Profit: ৳" . number_format($resellerCommission, 2) . ", Admin Share Deducted: ৳" . number_format($adminShare, 2),
                    'created_by' => Auth::id() ?: 1,
                ]);
            }

            $processedCount = 0;
            $totalAmountCollected = 0.00;
            $mikrotikApi = null;
            $radiusService = null;

            if ($shouldReactivate) {
                try {
                    $mikrotikApi = new MikrotikApiService();
                } catch (\Exception $e) {
                    // Non-blocking
                }
                try {
                    $radiusService = new RadiusService();
                } catch (\Exception $e) {
                    // Non-blocking
                }
            }

            foreach ($customers as $customer) {
                $oldDue = (float) ($customer->due_amount ?? 0);
                $oldExpiry = $customer->expiry_date;
                $monthlyBill = (float) ($customer->monthly_bill > 0 ? $customer->monthly_bill : 0);

                $amountToPay = $oldDue > 0 ? $oldDue : ($monthlyBill > 0 ? $monthlyBill : 0);
                if ($amountToPay <= 0) {
                    $amountToPay = 0;
                }

                $newDue = 0.00;
                $newExpiry = $oldExpiry;

                if ($shouldExtendValidity) {
                    $baseExpiry = ($oldExpiry && Carbon::parse($oldExpiry)->isFuture())
                        ? Carbon::parse($oldExpiry)
                        : Carbon::now();
                    $newExpiry = $baseExpiry->copy()->addMonth()->endOfDay();
                }

                $customer->due_amount = $newDue;
                if ($newExpiry) {
                    $customer->expiry_date = $newExpiry;
                }

                $isReactivated = false;
                if ($shouldReactivate) {
                    $customer->status = 'active';
                    $isReactivated = true;
                }

                $customer->save();

                // Create Customer Payment Record
                $invoiceNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);
                TenantCustomerPayment::create([
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
                    'notes' => $notes ?: "Bulk payment clearance for {$billingMonth}" . ($reseller ? " (Reseller: {$reseller->name})" : ""),
                ]);

                // Settle matching or open TenantCustomerInvoice
                try {
                    $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                        ->where('customer_id', $customer->id)
                        ->where(function ($q) use ($billingMonth) {
                            $q->where('billing_month', $billingMonth)
                              ->orWhereIn('status', ['unpaid', 'partial', 'overdue']);
                        })
                        ->orderBy('due_date', 'asc')
                        ->first();

                    if ($invoice) {
                        $invoice->paid_amount = $invoice->total_payable;
                        $invoice->due_amount = 0.00;
                        $invoice->status = 'paid';
                        $invoice->paid_at = Carbon::now();
                        $invoice->payment_method = $method;
                        $invoice->save();
                    }
                } catch (\Exception $e) {
                    // Non-blocking invoice sync
                }

                // Sync FreeRADIUS & MikroTik
                if ($radiusService) {
                    try {
                        $radiusService->syncCustomerSubscriber($customer);
                    } catch (\Exception $e) {
                        // Non-blocking
                    }
                }
                if ($mikrotikApi) {
                    try {
                        $mikrotikApi->syncCustomerToMikrotik($customer);
                    } catch (\Exception $e) {
                        // Non-blocking
                    }
                }

                $processedCount++;
                $totalAmountCollected += $amountToPay;
            }

            // Audit Trail Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => Auth::id() ?: 1,
                'actor_name' => Auth::user()?->name ?: 'Admin',
                'event_type' => 'CUSTOMER_BULK_PAYMENT_COLLECTED',
                'description' => "Bulk payment processed for {$processedCount} subscribers. Total Gross: ৳" . number_format($totalAmountCollected, 2) . ", Admin Share: ৳" . number_format($adminShare, 2) . ", Reseller Share: ৳" . number_format($resellerCommission, 2) . " via {$method}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'processed_count' => $processedCount,
                    'gross_bill' => $totalAmountCollected,
                    'admin_share' => $adminShare,
                    'reseller_commission' => $resellerCommission,
                    'reseller_id' => $reseller?->id,
                    'reseller_name' => $reseller?->name,
                    'customer_ids' => $customerIds,
                    'method' => $method,
                    'billing_month' => $billingMonth,
                ],
            ]);

            return response()->json([
                'success' => true,
                'processed_count' => $processedCount,
                'gross_bill' => number_format($totalAmountCollected, 2),
                'admin_share' => number_format($adminShare, 2),
                'reseller_commission' => number_format($resellerCommission, 2),
                'reseller_wallet_balance' => $reseller ? number_format($reseller->wallet_balance, 2) : null,
                'message' => "Bulk payment completed for {$processedCount} subscribers! Total: ৳" . number_format($totalAmountCollected, 2) . " (Admin Share: ৳" . number_format($adminShare, 2) . ")",
            ]);
        });
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
        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer.package', 'collector'])
            ->findOrFail($id);

        return view('tenant.customers.receipt', compact('tenant', 'payment'));
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
