<?php

namespace App\Http\Controllers\Tenant\Collector;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantCollectorDashboardController extends Controller
{
    /**
     * Resolve active authenticated Tenant.
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
     * Display the Bill Collector Hub Dashboard (Exact parity with Reseller Collector Dashboard).
     */
    public function index(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $collectorId = $authUser->id;

        // 1. Customer Metrics
        $customerQuery = TenantCustomer::where('tenant_id', $tenantId);

        $totalCustomers = (clone $customerQuery)->count();
        $activeCustomers = (clone $customerQuery)->where('status', 'active')->count();
        $dueCustomers = (clone $customerQuery)->where(function ($q) {
            $q->where('status', 'due')->orWhere('due_amount', '>', 0);
        })->count();
        $totalDueAmount = (float) (clone $customerQuery)->sum('due_amount');

        // 2. Collections Metrics for this Collector
        $todayPaymentsQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->whereDate('created_at', now()->toDateString());

        $monthPaymentsQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        $todayCollections = (float) $todayPaymentsQuery->sum('amount');
        $todayReceiptsCount = (int) $todayPaymentsQuery->count();
        $thisMonthCollections = (float) $monthPaymentsQuery->sum('amount');

        // Cash in Hand
        $totalCashCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $totalApprovedHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->whereIn('status', ['approved', 'verified'])
            ->sum('handed_over_amount');

        $cashInHand = max(0, $totalCashCollected - $totalApprovedHandovers);

        // Top Due Customers for Collector view
        $topDueCustomers = TenantCustomer::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('status', 'due')->orWhere('due_amount', '>', 0);
            })
            ->orderByDesc('due_amount')
            ->limit(5)
            ->get();

        // Recent Payments / Receipts by this Collector
        $recentPayments = TenantCustomerPayment::with(['customer.package', 'customer.zone'])
            ->where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->latest('id')
            ->limit(5)
            ->get();

        $stats = [
            'today_collections' => $todayCollections,
            'this_month_collections' => $thisMonthCollections,
            'due_customers' => $dueCustomers,
            'total_due_amount' => $totalDueAmount,
            'today_receipts_count' => $todayReceiptsCount,
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'cash_in_hand' => $cashInHand,
            // Aliases for compatibility
            'today_collected' => $todayCollections,
            'month_collected' => $thisMonthCollections,
            'due_customers_count' => $dueCustomers,
            'today_receipts' => $todayReceiptsCount,
        ];

        $currencySymbol = '৳';

        return view('tenant.collector.dashboard', compact(
            'tenant',
            'authUser',
            'stats',
            'topDueCustomers',
            'recentPayments',
            'currencySymbol'
        ));
    }

    /**
     * Display Area Due Customers for Collector & Management.
     */
    public function dueCustomers(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $collectorId = $authUser->id;
        $isCollector = $authUser->isCollector();

        // 1. Inputs & Filters
        $search = trim($request->input('search', ''));
        $zoneFilter = $request->input('zone_id', 'all');
        $packageFilter = $request->input('package_id', 'all');
        $dueStatusFilter = $request->input('due_status', 'all_due'); // all_due, expired_only, due_only, high_due
        $collectorFilter = $request->input('collector_id', 'all');
        $resellerFilter = $request->input('reseller_id', 'all');
        $perPage = $request->input('per_page', 20);

        // Core Closures: Standard definition of Expired and Due
        $isExpiredClosure = function ($q) {
            $q->whereIn('status', ['expired', 'suspended'])
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('expiry_date')
                      ->whereDate('expiry_date', '<=', Carbon::today());
              });
        };

        $isDueClosure = function ($q) {
            $q->where('due_amount', '>', 0)
              ->orWhere('status', 'due');
        };

        $isDueOrExpiredClosure = function ($q) use ($isDueClosure, $isExpiredClosure) {
            $q->where($isDueClosure)
              ->orWhere($isExpiredClosure);
        };

        // 2. Base Query: Include both Outstanding Due AND Expired lines
        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where($isDueOrExpiredClosure)
            ->with(['package', 'zone', 'reseller']);

        // Collector Scoping: If user is collector, filter to assigned customers if any assigned
        if ($isCollector) {
            $hasSpecificAssignments = TenantCustomer::where('tenant_id', $tenantId)
                ->where('assigned_collector_id', $collectorId)
                ->exists();
            if ($hasSpecificAssignments) {
                $query->where('assigned_collector_id', $collectorId);
            }
        } elseif ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            $query->where('assigned_collector_id', $collectorFilter);
        }

        // Reseller / Partner filter
        if ($resellerFilter === 'direct') {
            $query->whereNull('reseller_id');
        } elseif ($resellerFilter !== 'all' && !empty($resellerFilter)) {
            $query->where('reseller_id', $resellerFilter);
        }

        // Zone filter
        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', $zoneFilter);
        }

        // Package filter
        if ($packageFilter !== 'all' && !empty($packageFilter)) {
            $query->where('internet_package_id', $packageFilter);
        }

        // Due / Expired status filter
        if ($dueStatusFilter === 'expired_only') {
            $query->where($isExpiredClosure);
        } elseif ($dueStatusFilter === 'due_only') {
            $query->where($isDueClosure)->where(function ($q) {
                $q->whereNotIn('status', ['expired', 'suspended'])
                  ->where(function ($sub) {
                      $sub->whereNull('expiry_date')
                          ->orWhereDate('expiry_date', '>', Carbon::today());
                  });
            });
        } elseif ($dueStatusFilter === 'high_due') {
            $query->where('due_amount', '>=', 1000);
        }

        // Search text
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // 3. Smart Sorting: Positive Dues first, then Expired with 0 dues
        $query->orderByRaw("CASE WHEN due_amount > 0 THEN 0 ELSE 1 END, due_amount DESC, expiry_date ASC");
        if ($perPage === 'all') {
            $count = (clone $query)->count();
            $customers = $query->paginate($count > 0 ? $count : 20)->withQueryString();
        } else {
            $perPageInt = in_array((int)$perPage, [10, 15, 20, 30, 50, 100, 250, 500], true) ? (int)$perPage : 20;
            $customers = $query->paginate($perPageInt)->withQueryString();
        }

        // 4. Auxiliary Lists for Filters
        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->orderBy('name')->get();
        $packages = TenantInternetPackage::where('tenant_id', $tenantId)->orderBy('price')->get();
        $collectors = User::where('tenant_id', $tenantId)->whereIn('role', ['isp_collector', 'collector', 'reseller_collector'])->orderBy('name')->get();
        $resellers = TenantReseller::where('tenant_id', $tenantId)->orderBy('name')->get();

        // 5. High-Performance KPI Strip
        $baseCustomers = TenantCustomer::where('tenant_id', $tenantId);
        if ($isCollector && isset($hasSpecificAssignments) && $hasSpecificAssignments) {
            $baseCustomers->where('assigned_collector_id', $collectorId);
        }

        $totalDueCustomers = (clone $baseCustomers)->where($isDueOrExpiredClosure)->count();
        $totalDueAmount = (float) (clone $baseCustomers)->sum('due_amount');

        // Today collected
        $todayPaymentQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->whereDate('created_at', today());
        if ($isCollector) {
            $todayPaymentQuery->where('collected_by', $collectorId);
        }
        $todayCollected = (float) $todayPaymentQuery->sum('amount');

        // Cash In Hand Calculation (un-handed cash)
        $cashPaymentQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('payment_method', 'cash');
        if ($isCollector) {
            $cashPaymentQuery->where('collected_by', $collectorId);
        }
        $totalCashCollected = (float) $cashPaymentQuery->sum('amount');

        $handoverQuery = TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->whereIn('status', ['approved', 'verified']);
        if ($isCollector) {
            $handoverQuery->where('collector_id', $collectorId);
        }
        $totalApprovedHandovers = (float) $handoverQuery->sum('handed_over_amount');
        $cashInHand = max(0, $totalCashCollected - $totalApprovedHandovers);

        $expiredCustomers = (clone $baseCustomers)->where($isExpiredClosure)->count();
        $activeSubscribers = (clone $baseCustomers)->where($isDueClosure)->where(function ($q) {
            $q->whereNotIn('status', ['expired', 'suspended'])
              ->where(function ($sub) {
                  $sub->whereNull('expiry_date')
                      ->orWhereDate('expiry_date', '>', Carbon::today());
              });
        })->count();

        $stats = [
            'total_due_customers' => $totalDueCustomers,
            'total_due_amount' => $totalDueAmount,
            'today_collected' => $todayCollected,
            'cash_in_hand' => $cashInHand,
            'expired_customers' => $expiredCustomers,
            'active_subscribers' => $activeSubscribers,
        ];

        $currencySymbol = '৳';

        return view('tenant.collector.due_customers', compact(
            'tenant',
            'authUser',
            'customers',
            'zones',
            'packages',
            'collectors',
            'resellers',
            'stats',
            'search',
            'zoneFilter',
            'packageFilter',
            'dueStatusFilter',
            'collectorFilter',
            'resellerFilter',
            'perPage',
            'currencySymbol',
            'isCollector'
        ));
    }

    /**
     * Print Printable Field Due Sheet for Collector.
     */
    public function printDueSheet(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $collectorId = $authUser->id;
        $isCollector = $authUser->isCollector();

        $search = trim($request->input('search', ''));
        $zoneFilter = $request->input('zone_id', 'all');
        $packageFilter = $request->input('package_id', 'all');
        $dueStatusFilter = $request->input('due_status', 'all_due');
        $collectorFilter = $request->input('collector_id', 'all');
        $resellerFilter = $request->input('reseller_id', 'all');

        $isExpiredClosure = function ($q) {
            $q->whereIn('status', ['expired', 'suspended'])
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('expiry_date')
                      ->whereDate('expiry_date', '<=', Carbon::today());
              });
        };

        $isDueClosure = function ($q) {
            $q->where('due_amount', '>', 0)
              ->orWhere('status', 'due');
        };

        $isDueOrExpiredClosure = function ($q) use ($isDueClosure, $isExpiredClosure) {
            $q->where($isDueClosure)
              ->orWhere($isExpiredClosure);
        };

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where($isDueOrExpiredClosure)
            ->with(['package', 'zone', 'reseller']);

        if ($isCollector) {
            $hasSpecificAssignments = TenantCustomer::where('tenant_id', $tenantId)
                ->where('assigned_collector_id', $collectorId)
                ->exists();
            if ($hasSpecificAssignments) {
                $query->where('assigned_collector_id', $collectorId);
            }
        } elseif ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            $query->where('assigned_collector_id', $collectorFilter);
        }

        if ($resellerFilter === 'direct') {
            $query->whereNull('reseller_id');
        } elseif ($resellerFilter !== 'all' && !empty($resellerFilter)) {
            $query->where('reseller_id', $resellerFilter);
        }

        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', $zoneFilter);
        }

        if ($packageFilter !== 'all' && !empty($packageFilter)) {
            $query->where('internet_package_id', $packageFilter);
        }

        if ($dueStatusFilter === 'expired_only') {
            $query->where($isExpiredClosure);
        } elseif ($dueStatusFilter === 'due_only') {
            $query->where($isDueClosure)->where(function ($q) {
                $q->whereNotIn('status', ['expired', 'suspended'])
                  ->where(function ($sub) {
                      $sub->whereNull('expiry_date')
                          ->orWhereDate('expiry_date', '>', Carbon::today());
                  });
            });
        } elseif ($dueStatusFilter === 'high_due') {
            $query->where('due_amount', '>=', 1000);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByRaw("CASE WHEN due_amount > 0 THEN 0 ELSE 1 END, due_amount DESC, expiry_date ASC")->get();
        $totalDue = $customers->sum('due_amount');

        $activeZone = $zoneFilter !== 'all' ? TenantCoverageZone::find($zoneFilter) : null;
        $activeCollector = ($collectorFilter !== 'all' && !empty($collectorFilter)) ? User::find($collectorFilter) : ($isCollector ? $authUser : null);

        return view('tenant.collector.due_customers_print', compact(
            'tenant',
            'authUser',
            'customers',
            'totalDue',
            'activeZone',
            'activeCollector',
            'search',
            'dueStatusFilter'
        ));
    }

    /**
     * Display Daily Cash Handover for Collector.
     */
    public function handover(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $collectorId = $authUser->id;

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $handovers = TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->with(['verifier'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // 1. Calculate actual Un-handed Cash in Hand (whereNull('handover_id'))
        $availableCashInHand = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->whereNull('handover_id')
            ->where('payment_method', 'cash')
            ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
            ->sum('amount');

        // Total cash collected by this collector
        $totalCashCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->where('payment_method', 'cash')
            ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
            ->sum('amount');

        // Total approved handovers
        $totalApprovedHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->whereIn('status', ['approved', 'verified'])
            ->sum('handed_over_amount');

        // Pending submission handovers
        $pendingSubmissionHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->where('status', 'pending')
            ->sum('handed_over_amount');

        // Today collected (all payment methods)
        $todayCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->whereDate('paid_at', today())
            ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
            ->sum('amount');

        $stats = [
            'available_cash_in_hand' => $availableCashInHand,
            'pending_approval' => $pendingSubmissionHandovers,
            'total_approved_handed' => $totalApprovedHandovers,
            'total_cash_collected' => $totalCashCollected,
            'total_handovers_count' => TenantDailyCashHandover::where('tenant_id', $tenantId)->where('collector_id', $collectorId)->count(),
            'today_collected' => $todayCollected,
        ];

        $currencySymbol = '৳';

        return view('tenant.collector.handover', compact(
            'tenant',
            'authUser',
            'handovers',
            'stats',
            'availableCashInHand',
            'perPage',
            'currencySymbol'
        ));
    }

    /**
     * Submit Daily Cash Handover.
     */
    public function submitHandover(Request $request): RedirectResponse
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $handedOverAmount = (float) $request->amount;
        $handoverDate = now()->toDateString();

        DB::beginTransaction();
        try {
            // Retrieve all un-handed cash payments for this collector
            $cashPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('collected_by', $authUser->id)
                ->whereNull('handover_id')
                ->where('payment_method', 'cash')
                ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                ->get();

            $systemCash = (float) $cashPayments->sum('amount');
            $shortage = max(0, $systemCash - $handedOverAmount);
            $excess = max(0, $handedOverAmount - $systemCash);

            $handoverNo = TenantDailyCashHandover::generateHandoverNo($tenant->id, $handoverDate);

            $handover = TenantDailyCashHandover::create([
                'tenant_id' => $tenant->id,
                'handover_no' => $handoverNo,
                'collector_id' => $authUser->id,
                'handover_date' => $handoverDate,
                'shift_type' => 'daily',
                'system_collected_amount' => $systemCash,
                'handed_over_amount' => $handedOverAmount,
                'shortage_amount' => $shortage,
                'excess_amount' => $excess,
                'total_receipts_count' => $cashPayments->count(),
                'notes' => $request->notes ?: "HQ cash handover submitted by {$authUser->name}",
                'status' => 'pending',
            ]);

            // Lock constituent payments to prevent double-counting
            if ($cashPayments->isNotEmpty()) {
                TenantCustomerPayment::whereIn('id', $cashPayments->pluck('id'))
                    ->update(['handover_id' => $handover->id]);
            }

            // Audit Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'CASH_HANDOVER_SUBMITTED',
                'description' => "Collector '{$authUser->name}' submitted cash handover {$handoverNo} (৳" . number_format($handedOverAmount, 2) . "). Locked {$cashPayments->count()} cash receipt(s).",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'handover_id' => $handover->id,
                    'handover_no' => $handoverNo,
                    'handed_over_amount' => $handedOverAmount,
                    'system_collected_amount' => $systemCash,
                    'shortage_amount' => $shortage,
                    'excess_amount' => $excess,
                    'locked_receipts_count' => $cashPayments->count(),
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $currencySymbol = $tenant->currency_symbol ?? '৳';
        return back()->with('success', "দৈনিক ক্যাশ হ্যান্ডওভার ভাউচার {$handoverNo} ({$currencySymbol}" . number_format($handedOverAmount, 2) . ") সফলভাবে জমা দেওয়া হয়েছে এবং যাচাইয়ের জন্য অপেক্ষমাণ।");
    }

    /**
     * Show Dedicated Full-Page POS Billing & Payment Counter for a Subscriber.
     */
    public function showCollectPage(Request $request, $customerId): View
    {
        $tenant = $this->getTenant();
        $authUser = Auth::user();

        $customer = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['package', 'router', 'coverageZone', 'zone'])
            ->findOrFail($customerId);

        // Recent payment history for this customer (latest 5)
        $recentPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->with('collector')
            ->latest('id')
            ->limit(5)
            ->get();

        // Recent unpaid/due invoices
        $unpaidInvoices = [];
        if (class_exists('App\Models\TenantCustomerInvoice')) {
            $unpaidInvoices = \App\Models\TenantCustomerInvoice::where('tenant_id', $tenant->id)
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partially_paid', 'due', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->limit(6)
                ->get();
        }

        // Live MikroTik Session Check (defensive)
        $isOnline = false;
        try {
            if ($customer->router_id && !empty($customer->username) && class_exists('App\Services\Network\MikrotikApiService')) {
                // Quick check if customer is marked online
                $isOnline = $customer->status === 'active' && ($customer->last_online_at && Carbon::parse($customer->last_online_at)->diffInMinutes(now()) < 15);
            }
        } catch (\Throwable $e) {}

        return view('tenant.collector.collect', compact(
            'tenant',
            'authUser',
            'customer',
            'recentPayments',
            'unpaidInvoices',
            'isOnline'
        ));
    }

    /**
     * Show Dedicated 58mm / 80mm POS Thermal Receipt Slip.
     */
    public function showThermalReceipt(Request $request, $paymentId): View
    {
        $tenant = $this->getTenant();
        $payment = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->with(['customer.package', 'customer.coverageZone', 'collector'])
            ->findOrFail($paymentId);

        return view('tenant.collector.thermal_receipt', compact('tenant', 'payment'));
    }
}
