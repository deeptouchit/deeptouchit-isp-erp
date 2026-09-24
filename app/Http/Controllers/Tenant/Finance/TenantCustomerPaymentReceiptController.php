<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCustomerPaymentReceiptController extends Controller
{
    /**
     * Resolve active tenant
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

        return $tenant ?? abort(404, 'No active tenant found.');
    }

    /**
     * Display listing of Payment Collections & Receipts
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isCollector = $user && $user->isCollector();
        $isTechnician = $user && $user->isTechnician();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $allResellers = TenantReseller::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allCollectors = $isCollector 
            ? User::where('id', $user->id)->get(['id', 'name', 'email'])
            : User::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'email']);

        // Customer selection query for collection modal (Select2 formatted)
        $custSelectQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->with('package:id,name,package_name')
            ->orderBy('name');
        if ($isResellerUser) {
            $custSelectQuery->where('reseller_id', $userResellerId);
        } elseif ($isCollector) {
            $custSelectQuery->whereNull('reseller_id');
        }
        $allCustomers = $custSelectQuery->get(['id', 'customer_id', 'name', 'username', 'phone', 'monthly_bill', 'due_amount', 'package_id', 'package_name', 'reseller_id', 'zone'])
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'customer_id' => $c->customer_id ?: 'SO' . (1000 + $c->id),
                    'name' => $c->name,
                    'username' => $c->username ?: '--',
                    'phone' => $c->phone ?: '--',
                    'monthly_bill' => (float) ($c->monthly_bill ?: 0),
                    'due_amount' => (float) ($c->due_amount ?: 0),
                    'package_name' => $c->package?->name ?: ($c->package_name ?: '10Mbps'),
                    'zone' => $c->zone ?: '--',
                ];
            });

        // Request Filters
        $search = trim($request->input('search', ''));
        $methodFilter = $request->input('method', 'all');
        $statusFilter = $request->input('status', 'all');
        $collectorFilter = $isCollector ? (string) $user->id : $request->input('collector_id', 'all');
        $selectedMonth = $request->input('month', 'all');
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        // Resolve Period Presets & Date Range
        if (!empty($period) && $period !== 'custom' && $period !== 'all') {
            switch ($period) {
                case 'today':
                    $dateFrom = Carbon::today()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'yesterday':
                    $dateFrom = Carbon::yesterday()->toDateString();
                    $dateTo = Carbon::yesterday()->toDateString();
                    break;
                case 'this_week':
                    $dateFrom = Carbon::now()->startOfWeek()->toDateString();
                    $dateTo = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    break;
            }
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $period = 'custom';
        } elseif ($selectedMonth !== 'all' && !empty($selectedMonth)) {
            $period = 'custom';
            $dateFrom = Carbon::parse($selectedMonth . '-01')->startOfMonth()->toDateString();
            $dateTo = Carbon::parse($selectedMonth . '-01')->endOfMonth()->toDateString();
        } else {
            if (!$request->has('period') && !$request->has('date_from') && !$request->has('search') && !$request->has('collector_id') && !$request->has('reseller_id') && !$request->has('status') && !$request->has('method')) {
                $period = 'this_month';
                $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                $dateTo = Carbon::now()->endOfMonth()->toDateString();
            } else {
                $period = $period ?: 'all';
            }
        }

        // Reseller Scope Resolution
        if ($isResellerUser) {
            $selectedResellerId = (string) $userResellerId;
        } elseif ($isCollector) {
            $selectedResellerId = 'isp';
        } else {
            $selectedResellerId = $request->input('reseller_id', 'all');
        }

        // Filter Scope Closure
        $applyFilterScope = function ($q) use ($search, $methodFilter, $statusFilter, $collectorFilter, $selectedResellerId, $isResellerUser, $userResellerId, $isCollector, $user, $dateFrom, $dateTo) {
            if ($isResellerUser) {
                $q->where(function ($sub) use ($userResellerId) {
                    $sub->where('reseller_id', $userResellerId)
                        ->orWhereHas('customer', function ($cq) use ($userResellerId) {
                            $cq->where('reseller_id', $userResellerId);
                        });
                });
                if ($isCollector) {
                    $q->where('collected_by', $user->id);
                }
            } elseif ($isCollector) {
                $q->whereNull('reseller_id')->whereHas('customer', function ($cq) {
                    $cq->whereNull('reseller_id');
                })->where('collected_by', $user->id);
            } elseif ($selectedResellerId === 'isp') {
                $q->whereNull('reseller_id')->whereHas('customer', function ($cq) {
                    $cq->whereNull('reseller_id');
                });
            } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
                $resId = (int) $selectedResellerId;
                $q->where(function ($sub) use ($resId) {
                    $sub->where('reseller_id', $resId)
                        ->orWhereHas('customer', function ($cq) use ($resId) {
                            $cq->where('reseller_id', $resId);
                        });
                });
            }

            if (!empty($methodFilter) && $methodFilter !== 'all') {
                $q->where('payment_method', $methodFilter);
            }

            if (!empty($statusFilter) && $statusFilter !== 'all') {
                $q->where('status', $statusFilter);
            }

            if (!$isCollector && !empty($collectorFilter) && $collectorFilter !== 'all') {
                $q->where('collected_by', (int) $collectorFilter);
            }

            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) BETWEEN ? AND ?", [$dateFrom, $dateTo]);
            } elseif (!empty($dateFrom)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) >= ?", [$dateFrom]);
            } elseif (!empty($dateTo)) {
                $q->whereRaw("DATE(COALESCE(paid_at, created_at)) <= ?", [$dateTo]);
            }

            if (!empty($search)) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('customer_id', 'like', "%{$search}%")
                               ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }
        };

        // Base Payments Query
        $query = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'reseller', 'collector', 'invoice']);

        $applyFilterScope($query);

        $payments = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Compute 6 KPI Stats (Scoped to active filter)
        $kpiBaseQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereIn('status', ['completed', 'paid', 'approved']);

        $applyFilterScope($kpiBaseQuery);

        $allKpiPayments = (clone $kpiBaseQuery)->get();
        $totalCollections = (float) $allKpiPayments->sum('amount');
        $totalReceiptsCount = $allKpiPayments->count();
        $totalDiscountGiven = (float) $allKpiPayments->sum('discount');

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        $todayCollections = (float) $allKpiPayments->filter(function ($p) use ($todayStart, $todayEnd) {
            $date = $p->paid_at ?: $p->created_at;
            return $date && Carbon::parse($date)->between($todayStart, $todayEnd);
        })->sum('amount');

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();
        $thisMonthCollections = (float) $allKpiPayments->filter(function ($p) use ($thisMonthStart, $thisMonthEnd) {
            $date = $p->paid_at ?: $p->created_at;
            return $date && Carbon::parse($date)->between($thisMonthStart, $thisMonthEnd);
        })->sum('amount');

        $cashCollections = (float) $allKpiPayments->where('payment_method', 'cash')->sum('amount');
        $digitalCollections = (float) $allKpiPayments->where('payment_method', '!=', 'cash')->sum('amount');

        // Distinct available billing months for filter dropdown
        $availableMonths = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereNotNull('billing_month')
            ->distinct()
            ->orderByDesc('billing_month')
            ->pluck('billing_month')
            ->toArray();

        $currentMonth = Carbon::now()->format('Y-m');
        if (!in_array($currentMonth, $availableMonths)) {
            array_unshift($availableMonths, $currentMonth);
        }

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.finance.payments', compact(
            'tenant',
            'payments',
            'allResellers',
            'allCollectors',
            'allCustomers',
            'availableMonths',
            'selectedMonth',
            'methodFilter',
            'statusFilter',
            'collectorFilter',
            'selectedResellerId',
            'period',
            'dateFrom',
            'dateTo',
            'search',
            'perPage',
            'currencySymbol',
            'isResellerUser',
            'isCollector',
            'isTechnician',
            'totalCollections',
            'todayCollections',
            'thisMonthCollections',
            'totalReceiptsCount',
            'cashCollections',
            'digitalCollections',
            'totalDiscountGiven'
        ));
    }

    /**
     * Store / Record a New Customer Payment
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $validated = $request->validate([
            'customer_id' => 'required|exists:tenant_customers,id',
            'amount' => 'required|numeric|min:0.01',
            'discount' => 'nullable|numeric|min:0',
            'billing_month' => 'required|string',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,bank_transfer,pos,online,other',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
            'send_sms' => 'nullable|boolean',
        ]);

        $custQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($isResellerUser) {
            $custQuery->where('reseller_id', $userResellerId);
        }
        $customer = $custQuery->findOrFail($validated['customer_id']);

        $amount = (float) $validated['amount'];
        $discount = (float) ($validated['discount'] ?? 0.00);
        $totalCoverage = $amount + $discount;
        $paidAt = !empty($validated['paid_at']) ? Carbon::parse($validated['paid_at']) : Carbon::now();
        $method = $validated['payment_method'];
        $trxId = $validated['transaction_id'] ?: ('TRX-' . strtoupper(substr(md5(uniqid() . $customer->id), 0, 10)));
        $notes = $validated['notes'] ?? "Payment for {$validated['billing_month']}";

        // 1. Generate Money Receipt Number
        $receiptNo = TenantCustomerPayment::generateInvoiceNo($tenant->id);

        // 2. Find and Settle Invoice
        $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->where(function ($q) use ($validated) {
                $q->where('billing_month', $validated['billing_month'])
                  ->orWhereIn('status', ['unpaid', 'partially_paid', 'overdue']);
            })
            ->orderBy('due_date', 'asc')
            ->first();

        if ($invoice) {
            $invoiceDue = $invoice->due_amount > 0 ? (float) $invoice->due_amount : ((float) $invoice->total_payable - (float) $invoice->paid_amount);
            if ($totalCoverage >= $invoiceDue) {
                $invoice->paid_amount = $invoice->total_payable;
                $invoice->due_amount = 0.00;
                $invoice->status = 'paid';
            } else {
                $invoice->paid_amount = (float) $invoice->paid_amount + $totalCoverage;
                $invoice->due_amount = max(0, (float) $invoice->total_payable - (float) $invoice->paid_amount);
                $invoice->status = 'partially_paid';
            }
            $invoice->paid_at = $paidAt;
            $invoice->payment_method = $method;
            $invoice->save();
        }

        // 3. Update Customer Balance, Due, Status & Expiry
        $wasBlocked = in_array($customer->status, ['due', 'expired', 'inactive', 'disabled']);
        if ($customer->due_amount > 0) {
            $customer->due_amount = max(0, (float) $customer->due_amount - $totalCoverage);
        }

        // Extend expiry if active package and current expiry is in past
        if ($customer->status === 'expired' || !$customer->expiry_date || $customer->expiry_date->isPast()) {
            $customer->expiry_date = Carbon::now()->addMonth();
        }
        if ($wasBlocked) {
            $customer->status = 'active';
        }
        $customer->save();

        // 4. Create Payment Record
        $payment = TenantCustomerPayment::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $customer->reseller_id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice?->id,
            'invoice_no' => $receiptNo,
            'billing_month' => $validated['billing_month'],
            'amount' => $amount,
            'discount' => $discount,
            'payment_method' => $method,
            'collected_by' => Auth::id() ?: 1,
            'status' => 'completed',
            'paid_at' => $paidAt,
            'transaction_id' => $trxId,
            'notes' => $notes,
            'sms_sent' => false,
        ]);

        // 5. Unblock / Sync MikroTik & FreeRADIUS
        $syncMsg = '';
        try {
            $mikrotikApi = new MikrotikApiService();
            $syncRes = $mikrotikApi->syncCustomerToMikrotik($customer);
            if (!empty($syncRes['success'])) {
                $syncMsg .= ' & MikroTik unblocked';
            }
        } catch (\Exception $e) {
            // Non-blocking
        }

        try {
            $radiusService = new RadiusService();
            $radiusRes = $radiusService->syncCustomerSubscriber($customer);
            if (!empty($radiusRes['success'])) {
                $syncMsg .= ' & FreeRADIUS active';
            }
        } catch (\Exception $e) {
            // Non-blocking
        }

        // 6. Handle SMS Notification if requested
        $sendSms = !empty($validated['send_sms']);
        if ($sendSms && !empty($customer->phone)) {
            $payment->sms_sent = true;
            $payment->sms_sent_at = Carbon::now();
            $payment->save();
        }

        // 7. Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'PAYMENT_COLLECTED',
            'description' => "Collected ৳" . number_format($amount, 2) . " from subscriber '{$customer->name}' ({$customer->username}) via {$payment->payment_method_name}. Receipt: {$receiptNo}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'payment_id' => $payment->id,
                'receipt_no' => $receiptNo,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'discount' => $discount,
            ],
        ]);

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $msg = "Payment of {$currencySymbol}" . number_format($amount, 2) . " recorded successfully! (Money Receipt: {$receiptNo}){$syncMsg}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'payment_id' => $payment->id,
                'receipt_no' => $receiptNo,
                'receipt_url' => route('tenant.finance.payments.show', $payment->id),
            ]);
        }

        return redirect()->route('tenant.finance.payments')->with('success', $msg);
    }

    /**
     * Fetch Single Money Receipt Details for View/POS Print Modal
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $isCollector = $user && $user->isCollector();

        $paymentQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'customer.package', 'reseller', 'collector', 'invoice']);

        if ($isResellerUser) {
            $paymentQuery->where(function ($q) use ($userResellerId) {
                $q->where('reseller_id', $userResellerId)
                  ->orWhereHas('customer', function ($cq) use ($userResellerId) {
                      $cq->where('reseller_id', $userResellerId);
                  });
            });
            if ($isCollector) {
                $paymentQuery->where('collected_by', $user->id);
            }
        } elseif ($isCollector) {
            $paymentQuery->whereNull('reseller_id')
                ->whereHas('customer', function ($cq) {
                    $cq->whereNull('reseller_id');
                })
                ->where('collected_by', $user->id);
        }

        $payment = $paymentQuery->findOrFail($id);
        $customer = $payment->customer;
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        $receiptData = [
            'id' => $payment->id,
            'receipt_no' => $payment->invoice_no,
            'billing_month' => $payment->formatted_month,
            'amount' => (float) $payment->amount,
            'amount_formatted' => $currencySymbol . number_format($payment->amount, 2),
            'discount' => (float) $payment->discount,
            'discount_formatted' => $currencySymbol . number_format($payment->discount, 2),
            'total_received' => (float) ($payment->amount + $payment->discount),
            'total_received_formatted' => $currencySymbol . number_format($payment->amount + $payment->discount, 2),
            'payment_method' => $payment->payment_method,
            'payment_method_name' => $payment->payment_method_name,
            'method_icon' => $payment->method_icon,
            'transaction_id' => $payment->transaction_id ?: 'N/A',
            'paid_at' => $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : $payment->created_at->format('d M Y, h:i A'),
            'paid_date' => $payment->paid_at ? $payment->paid_at->format('d M Y') : $payment->created_at->format('d M Y'),
            'paid_time' => $payment->paid_at ? $payment->paid_at->format('h:i A') : $payment->created_at->format('h:i A'),
            'notes' => $payment->notes ?: 'Customer bill payment clearance',
            'status' => $payment->status,
            'status_badge' => $payment->status_badge,
            'sms_sent' => (bool) $payment->sms_sent,
            'sms_sent_at' => $payment->sms_sent_at ? Carbon::parse($payment->sms_sent_at)->format('d M Y, h:i A') : null,
            'collector_name' => $payment->collector?->name ?? 'System Admin',
            'company' => [
                'name' => $tenant->company_name ?: ($tenant->name ?? 'ISP Management Portal'),
                'phone' => $tenant->phone ?: ($tenant->support_phone ?? '01700-000000'),
                'email' => $tenant->email ?: 'support@somitysoft.com',
                'address' => $tenant->address ?: 'Head Office, Internet Service Provider',
            ],
            'customer' => [
                'id' => $customer?->id,
                'customer_code' => $customer?->customer_id ?: 'SO' . (1000 + ($customer?->id ?? 0)),
                'name' => $customer?->name ?? 'Unknown Subscriber',
                'username' => $customer?->username ?? '--',
                'phone' => $customer?->phone ?? '--',
                'address' => $customer?->address ?? 'N/A',
                'zone' => $customer?->zone ?? 'Default Zone',
                'current_due' => (float) ($customer?->due_amount ?? 0),
                'current_due_formatted' => $currencySymbol . number_format($customer?->due_amount ?? 0, 2),
                'package_name' => $customer?->mikrotik_profile_name ?: ($customer?->package?->name ?? ($customer?->package_name ?? '10Mbps')),
                'monthly_bill' => (float) ($customer?->monthly_bill ?? 0),
            ],
            'reseller' => $payment->reseller ? [
                'name' => $payment->reseller->name,
                'code' => $payment->reseller->code,
                'phone' => $payment->reseller->phone,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'payment' => $receiptData,
        ]);
    }

    /**
     * Trigger / Resend SMS Confirmation
     */
    public function sendSms(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $paymentQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer']);

        if ($isResellerUser) {
            $paymentQuery->where('reseller_id', $userResellerId);
        }

        $payment = $paymentQuery->findOrFail($id);
        $customer = $payment->customer;

        if (!$customer || empty($customer->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber has no valid phone number for SMS dispatch.',
            ], 422);
        }

        $payment->sms_sent = true;
        $payment->sms_sent_at = Carbon::now();
        $payment->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'PAYMENT_SMS_DISPATCHED',
            'description' => "Sent payment confirmation SMS for Receipt: {$payment->invoice_no} to {$customer->phone}.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Payment confirmation SMS dispatched successfully to {$customer->phone}.",
            'sms_sent_at' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Void / Cancel Payment Receipt
     */
    public function voidPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $paymentQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'invoice']);

        if ($isResellerUser) {
            $paymentQuery->where('reseller_id', $userResellerId);
        }

        $payment = $paymentQuery->findOrFail($id);

        if ($payment->status === 'void' || $payment->status === 'refunded') {
            return response()->json([
                'success' => false,
                'message' => 'This payment receipt is already voided.',
            ], 422);
        }

        $reason = $request->input('reason', 'Payment voided / reversed by admin');
        $customer = $payment->customer;
        $reversalAmount = (float) $payment->amount + (float) $payment->discount;

        // 1. Re-adjust Customer Due
        if ($customer) {
            $customer->due_amount = (float) $customer->due_amount + $reversalAmount;
            $customer->save();
        }

        // 2. Re-adjust Invoice if linked
        if ($payment->invoice) {
            $inv = $payment->invoice;
            $inv->paid_amount = max(0, (float) $inv->paid_amount - $reversalAmount);
            $inv->due_amount = max(0, (float) $inv->total_payable - (float) $inv->paid_amount);
            $inv->status = $inv->paid_amount > 0 ? 'partially_paid' : 'unpaid';
            $inv->save();
        }

        // 3. Mark Payment Status
        $payment->status = 'void';
        $payment->notes = ($payment->notes ? $payment->notes . " | " : "") . "VOIDED: {$reason}";
        $payment->save();

        // 4. Audit Log
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => Auth::id() ?: 1,
            'actor_name' => Auth::user()?->name ?: 'Admin',
            'event_type' => 'PAYMENT_VOIDED',
            'description' => "Voided payment receipt {$payment->invoice_no} (৳" . number_format($payment->amount, 2) . "). Reason: {$reason}",
            'ip_address' => $request->ip(),
        ]);

        $msg = "Payment receipt {$payment->invoice_no} has been voided successfully.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('tenant.finance.payments')->with('success', $msg);
    }

    /**
     * Stream CSV Export of Payments
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $user = Auth::user();
        $isResellerUser = $user && ($user->isResellerUser() || !empty($user->reseller_id));
        $userResellerId = $user?->reseller_id;

        $search = trim($request->input('search', ''));
        $methodFilter = $request->input('method', 'all');
        $statusFilter = $request->input('status', 'all');
        $collectorFilter = $request->input('collector_id', 'all');
        $selectedMonth = $request->input('month', 'all');
        $period = $request->input('period', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!empty($period) && $period !== 'custom' && $period !== 'all') {
            switch ($period) {
                case 'today':
                    $dateFrom = Carbon::today()->toDateString();
                    $dateTo = Carbon::today()->toDateString();
                    break;
                case 'yesterday':
                    $dateFrom = Carbon::yesterday()->toDateString();
                    $dateTo = Carbon::yesterday()->toDateString();
                    break;
                case 'this_week':
                    $dateFrom = Carbon::now()->startOfWeek()->toDateString();
                    $dateTo = Carbon::now()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $dateFrom = Carbon::now()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $dateFrom = Carbon::now()->subMonth()->startOfMonth()->toDateString();
                    $dateTo = Carbon::now()->subMonth()->endOfMonth()->toDateString();
                    break;
            }
        } elseif (!empty($dateFrom) || !empty($dateTo)) {
            $period = 'custom';
        } elseif ($selectedMonth !== 'all' && !empty($selectedMonth)) {
            $period = 'custom';
            $dateFrom = Carbon::parse($selectedMonth . '-01')->startOfMonth()->toDateString();
            $dateTo = Carbon::parse($selectedMonth . '-01')->endOfMonth()->toDateString();
        }

        $isCollector = $user && $user->isCollector();
        $selectedResellerId = $isResellerUser ? (string) $userResellerId : ($isCollector ? 'isp' : $request->input('reseller_id', 'all'));

        $query = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'reseller', 'collector'])
            ->orderByDesc('id');

        if ($isResellerUser) {
            $query->where(function ($sub) use ($userResellerId) {
                $sub->where('reseller_id', $userResellerId)
                    ->orWhereHas('customer', function ($cq) use ($userResellerId) {
                        $cq->where('reseller_id', $userResellerId);
                    });
            });
            if ($isCollector) {
                $query->where('collected_by', $user->id);
            }
        } elseif ($isCollector) {
            $query->whereNull('reseller_id')->whereHas('customer', function ($cq) {
                $cq->whereNull('reseller_id');
            })->where('collected_by', $user->id);
        } elseif ($selectedResellerId === 'isp') {
            $query->whereNull('reseller_id')->whereHas('customer', function ($cq) {
                $cq->whereNull('reseller_id');
            });
        } elseif (!empty($selectedResellerId) && $selectedResellerId !== 'all') {
            $resId = (int) $selectedResellerId;
            $query->where(function ($sub) use ($resId) {
                $sub->where('reseller_id', $resId)
                    ->orWhereHas('customer', function ($cq) use ($resId) {
                        $cq->where('reseller_id', $resId);
                    });
            });
        }

        if (!empty($methodFilter) && $methodFilter !== 'all') {
            $query->where('payment_method', $methodFilter);
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($collectorFilter) && $collectorFilter !== 'all') {
            $query->where('collected_by', (int) $collectorFilter);
        }

        if (!empty($dateFrom) && !empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) BETWEEN ? AND ?", [$dateFrom, $dateTo]);
        } elseif (!empty($dateFrom)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) >= ?", [$dateFrom]);
        } elseif (!empty($dateTo)) {
            $query->whereRaw("DATE(COALESCE(paid_at, created_at)) <= ?", [$dateTo]);
        }

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%")
                           ->orWhere('customer_id', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->get();
        $filename = 'payment_collections_' . ($dateFrom ? $dateFrom . '_to_' . ($dateTo ?: $dateFrom) : date('Ymd_His')) . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Receipt No',
                'Date & Time',
                'Subscriber Name',
                'Customer ID',
                'PPPoE Username',
                'Phone',
                'Billing Month',
                'Payment Method',
                'Amount (BDT)',
                'Discount (BDT)',
                'Transaction ID',
                'Collected By',
                'Reseller / Scope',
                'Status',
                'Notes',
            ]);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->invoice_no,
                    $p->paid_at ? $p->paid_at->format('Y-m-d H:i:s') : $p->created_at->format('Y-m-d H:i:s'),
                    $p->customer?->name ?? 'N/A',
                    $p->customer?->customer_id ?? 'N/A',
                    $p->customer?->username ?? 'N/A',
                    $p->customer?->phone ?? 'N/A',
                    $p->billing_month,
                    $p->payment_method_name,
                    number_format($p->amount, 2, '.', ''),
                    number_format($p->discount, 2, '.', ''),
                    $p->transaction_id,
                    $p->collector?->name ?? 'Admin',
                    $p->reseller?->name ?? 'ISP HQ',
                    $p->status,
                    $p->notes,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Approve a Pending Payment (e.g. Bangla QR / Offline Submission)
     */
    public function approvePayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $authUser = Auth::user();

        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'reseller'])
            ->findOrFail($id);

        if (in_array(strtolower($payment->status), ['paid', 'completed', 'approved'])) {
            $msg = "Payment receipt #{$payment->invoice_no} has already been approved and settled.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('info', $msg);
        }

        $customer = $payment->customer;
        $amount = (float) $payment->amount;
        $discount = (float) ($payment->discount ?? 0);
        $totalCredited = $amount + $discount;
        $reseller = $payment->reseller;

        DB::transaction(function () use ($tenant, $payment, $customer, $amount, $discount, $totalCredited, $reseller, $authUser) {
            // 1. Mark Payment as Paid / Approved
            $payment->status = 'paid';
            $payment->paid_at = Carbon::now();
            $payment->save();

            // 2. Adjust Customer Due & Validity
            if ($customer) {
                $customer->due_amount = max(0, (float) $customer->due_amount - $totalCredited);

                // Extend validity
                $currentExpiry = ($customer->expiry_date && Carbon::parse($customer->expiry_date)->isFuture())
                    ? Carbon::parse($customer->expiry_date)
                    : Carbon::now();
                $customer->expiry_date = $currentExpiry->addMonth();

                // Restore active status
                if (in_array($customer->status, ['due', 'expired', 'disabled', 'suspended', 'inactive'])) {
                    $customer->status = 'active';
                }
                $customer->save();
            }

            // 3. Credit Reseller Wallet Commission (if applicable for Bangla QR / Reseller payment)
            if ($reseller && in_array(strtolower($payment->payment_method), ['bangla_qr', 'banglaqr'])) {
                $commissionRate = (float) ($reseller->commission_rate ?? 0);
                $resellerCommission = round(($amount * $commissionRate) / 100, 2);

                if ($resellerCommission > 0) {
                    $balanceBefore = (float) $reseller->wallet_balance;
                    $reseller->increment('wallet_balance', $resellerCommission);
                    $reseller->refresh();

                    TenantResellerWalletTransaction::create([
                        'tenant_id' => $tenant->id,
                        'reseller_id' => $reseller->id,
                        'trx_id' => 'TRX-' . strtoupper(Str::random(10)),
                        'type' => 'CREDIT',
                        'amount' => $resellerCommission,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $reseller->wallet_balance,
                        'payment_method' => 'BANGLA_QR',
                        'description' => "Commission earned on approved Bangla QR payment '{$payment->invoice_no}' for '{$customer?->username}' ({$customer?->name}). Bill: ৳" . number_format($amount, 2) . ", Commission ({$commissionRate}%): ৳" . number_format($resellerCommission, 2) . ".",
                        'created_by' => $authUser->id,
                    ]);
                }
            }

            // 4. Settle Open Customer Invoice
            try {
                if ($customer) {
                    $invoice = TenantCustomerInvoice::where('tenant_id', $tenant->id)
                        ->where('customer_id', $customer->id)
                        ->whereIn('status', ['unpaid', 'partial', 'overdue', 'partially_paid'])
                        ->orderBy('due_date', 'asc')
                        ->first();

                    if ($invoice) {
                        if ($amount >= $invoice->total_payable) {
                            $invoice->paid_amount = $invoice->total_payable;
                            $invoice->due_amount = 0.00;
                            $invoice->status = 'paid';
                        } else {
                            $invoice->paid_amount = (float)$invoice->paid_amount + $amount;
                            $invoice->due_amount = max(0, (float)$invoice->total_payable - (float)$invoice->paid_amount);
                            $invoice->status = 'partially_paid';
                        }
                        $invoice->paid_at = Carbon::now();
                        $invoice->payment_method = $payment->payment_method;
                        $invoice->save();
                    }
                }
            } catch (\Exception $e) {
                // Non-blocking
            }

            // 5. Audit Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'PAYMENT_APPROVED',
                'description' => "Approved payment '{$payment->invoice_no}' of ৳" . number_format($amount, 2) . " for customer '{$customer?->name}' ({$customer?->username}). Line activated.",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'customer_id' => $customer?->id,
                    'amount' => $amount,
                ],
            ]);
        });

        // 6. Synchronize to MikroTik & FreeRADIUS
        $syncNotes = '';
        if ($customer) {
            try {
                $mikrotikApi = new MikrotikApiService();
                $mikrotikApi->syncCustomerToMikrotik($customer);
                $syncNotes .= ' & MikroTik Synced';
            } catch (\Exception $e) {}

            try {
                $radiusService = new RadiusService();
                $radiusService->syncCustomerSubscriber($customer);
                $syncNotes .= ' & FreeRADIUS Active';
            } catch (\Exception $e) {}
        }

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $successMsg = "Payment #{$payment->invoice_no} ({$currencySymbol}" . number_format($amount, 2) . ") approved successfully! Customer line is now active{$syncNotes}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Reject / Void a Pending Payment
     */
    public function rejectPayment(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $tenant = $this->getTenant();
        $authUser = Auth::user();

        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer', 'reseller'])
            ->findOrFail($id);

        $reason = $request->input('reason', 'Payment verification failed or TrxID invalid.');

        $payment->status = 'void';
        $payment->notes = trim(($payment->notes ? $payment->notes . ' | ' : '') . "Rejected: " . $reason);
        $payment->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'PAYMENT_REJECTED',
            'description' => "Rejected pending payment '{$payment->invoice_no}' of ৳" . number_format($payment->amount, 2) . ". Reason: {$reason}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $msg = "Payment '{$payment->invoice_no}' has been rejected.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
