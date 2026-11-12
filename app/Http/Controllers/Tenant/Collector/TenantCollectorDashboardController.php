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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

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

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

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
        $currencySymbol = $tenant?->currency_symbol ?? '৳';

        return view('tenant.collector.due_customers_print', compact(
            'tenant',
            'authUser',
            'customers',
            'totalDue',
            'activeZone',
            'activeCollector',
            'search',
            'dueStatusFilter',
            'currencySymbol'
        ));
    }

    /**
     * Display Daily Cash Handover for Collector / Admin Hub.
     */
    public function handover(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $isAdmin = $authUser && ($authUser->isOwner() || $authUser->isIspAdmin());

        $allCollectors = User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'email', 'role']);
        $allResellers = TenantReseller::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        $query = TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->with(['collector.reseller', 'verifier']);

        // 1. Collector Scope / Filter
        $collectorFilter = $request->input('collector_id', $isAdmin ? 'all' : (string) $authUser->id);
        if (!$isAdmin) {
            $collectorFilter = (string) $authUser->id;
            $query->where('collector_id', $authUser->id);
        } elseif ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            $query->where('collector_id', (int) $collectorFilter);
        }

        // 1b. Partner / Reseller Filter
        $partnerFilter = $request->input('partner_id', 'all');
        if (!empty($partnerFilter) && $partnerFilter !== 'all') {
            if ($partnerFilter === 'hq') {
                $query->whereHas('collector', fn($cq) => $cq->whereNull('reseller_id'));
            } else {
                $query->whereHas('collector', fn($cq) => $cq->where('reseller_id', (int) $partnerFilter));
            }
        }

        // 2. Status Filter
        $statusFilter = $request->input('status', 'all');
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // 3. Shift Filter
        $shiftFilter = $request->input('shift', 'all');
        if (!empty($shiftFilter) && $shiftFilter !== 'all') {
            $query->where('shift_type', $shiftFilter);
        }

        // 4. Period & Date Filters
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
        }

        if ($dateFrom) {
            $query->whereDate('handover_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('handover_date', '<=', $dateTo);
        }

        // 5. Intelligent Search Filter
        $search = trim($request->input('search', ''));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('handover_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('manager_remarks', 'like', "%{$search}%")
                  ->orWhereHas('collector', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $handovers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // 6. Active More Filters Count
        $activeMoreCount = 0;
        if (!empty($partnerFilter) && $partnerFilter !== 'all') $activeMoreCount++;
        if (!empty($period) && $period !== 'all') $activeMoreCount++;
        if (!empty($dateFrom)) $activeMoreCount++;
        if (!empty($dateTo)) $activeMoreCount++;
        if (!empty($shiftFilter) && $shiftFilter !== 'all') $activeMoreCount++;

        // 7. Calculate actual Un-handed Cash in Hand
        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        $unhandedCashQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->when($hasHandoverCol, fn($q) => $q->whereNull('handover_id'))
            ->where('payment_method', 'cash')
            ->whereNotIn('status', ['void', 'refunded', 'cancelled']);

        if (!$isAdmin) {
            $unhandedCashQuery->where('collected_by', $authUser->id);
        } elseif ($collectorFilter !== 'all' && !empty($collectorFilter)) {
            $unhandedCashQuery->where('collected_by', (int) $collectorFilter);
        }

        $availableCashInHand = (float) $unhandedCashQuery->sum('amount');

        // Stats Query
        $statsBaseQuery = clone $query;
        $allStatsHandovers = $statsBaseQuery->get();

        $pendingApproval = (float) $allStatsHandovers->where('status', 'pending')->sum('handed_over_amount');
        $approvedHanded = (float) $allStatsHandovers->where('status', 'approved')->sum('handed_over_amount');
        $totalCash = $pendingApproval + $approvedHanded;
        $totalHandoversCount = $allStatsHandovers->count();

        // Today collected cash in system
        $todayCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->when(!$isAdmin, fn($q) => $q->where('collected_by', $authUser->id))
            ->whereDate('paid_at', today())
            ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
            ->sum('amount');

        $stats = [
            'available_cash_in_hand' => $availableCashInHand,
            'pending_approval' => $pendingApproval,
            'total_approved_handed' => $approvedHanded,
            'total_cash_collected' => $totalCash,
            'total_handovers_count' => $totalHandoversCount,
            'today_collected' => $todayCollected,
        ];

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

        return view('tenant.collector.handover', compact(
            'tenant',
            'authUser',
            'handovers',
            'stats',
            'availableCashInHand',
            'perPage',
            'currencySymbol',
            'isAdmin',
            'allCollectors',
            'allResellers',
            'partnerFilter',
            'collectorFilter',
            'statusFilter',
            'shiftFilter',
            'period',
            'dateFrom',
            'dateTo',
            'search',
            'activeMoreCount'
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
        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');

        // Admin/Owner cash collections are auto-vaulted (Directly Approved into Vault)
        $isAdminUser = $authUser && ($authUser->isOwner() || $authUser->isIspAdmin());
        $status = $isAdminUser ? 'approved' : 'pending';

        DB::beginTransaction();
        try {
            // Retrieve all un-handed cash payments for this collector
            $cashPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('collected_by', $authUser->id)
                ->when($hasHandoverCol, fn($q) => $q->whereNull('handover_id'))
                ->where('payment_method', 'cash')
                ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                ->get();

            $systemCash = (float) $cashPayments->sum('amount');
            $shortage = max(0, $systemCash - $handedOverAmount);
            $excess = max(0, $handedOverAmount - $systemCash);

            $handoverNo = TenantDailyCashHandover::generateHandoverNo($tenant->id, $handoverDate);
            $currencySymbol = $tenant?->currency_symbol ?? '৳';
            $formattedAmount = $currencySymbol . number_format($handedOverAmount, 2);

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
                'notes' => $request->notes ?: ($isAdminUser
                    ? __('Admin direct office vault cash closing by :name', ['name' => $authUser->name])
                    : __('HQ cash handover submitted by :name', ['name' => $authUser->name])),
                'status' => $status,
                'verified_by' => $isAdminUser ? $authUser->id : null,
                'verified_at' => $isAdminUser ? now() : null,
                'manager_remarks' => $isAdminUser ? __('Auto-approved Admin direct vault cash closing') : null,
            ]);

            // Lock constituent payments to prevent double-counting
            if ($hasHandoverCol && $cashPayments->isNotEmpty()) {
                TenantCustomerPayment::whereIn('id', $cashPayments->pluck('id'))
                    ->update(['handover_id' => $handover->id]);
            }

            // Audit Log
            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => $isAdminUser ? 'CASH_HANDOVER_APPROVED' : 'CASH_HANDOVER_SUBMITTED',
                'description' => $isAdminUser
                    ? __("Admin ':name' closed and auto-approved cash voucher :no (:amount) directly into vault. Locked :count cash receipt(s).", [
                        'name' => $authUser->name,
                        'no' => $handoverNo,
                        'amount' => $formattedAmount,
                        'count' => $cashPayments->count(),
                    ])
                    : __("Collector ':name' submitted cash handover :no (:amount). Locked :count cash receipt(s).", [
                        'name' => $authUser->name,
                        'no' => $handoverNo,
                        'amount' => $formattedAmount,
                        'count' => $cashPayments->count(),
                    ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'handover_id' => $handover->id,
                    'handover_no' => $handoverNo,
                    'handed_over_amount' => $handedOverAmount,
                    'system_collected_amount' => $systemCash,
                    'shortage_amount' => $shortage,
                    'excess_amount' => $excess,
                    'status' => $status,
                    'locked_receipts_count' => $cashPayments->count(),
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $currencySymbol = $tenant?->currency_symbol ?? '৳';
        $formattedAmount = $currencySymbol . number_format($handedOverAmount, 2);
        $successMsg = $isAdminUser
            ? __('Admin cash handover voucher :no (:amount) has been approved and transferred directly into the vault.', [
                'no' => $handoverNo,
                'amount' => $formattedAmount,
            ])
            : __('Cash handover voucher :no (:amount) submitted successfully and awaiting manager verification.', [
                'no' => $handoverNo,
                'amount' => $formattedAmount,
            ]);

        return back()->with('success', $successMsg);
    }

    /**
     * Show / Fetch single Cash Handover Voucher details for Modal & Print
     */
    public function showHandover(Request $request, int $id): JsonResponse
    {
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)
            ->with(['collector.reseller', 'verifier'])
            ->findOrFail($id);

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

        $hasHandoverCol = Schema::hasColumn('tenant_customer_payments', 'handover_id');
        $payments = collect();
        if ($hasHandoverCol) {
            $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('handover_id', $handover->id)
                ->with('customer:id,customer_id,name,username,phone')
                ->get();
        }

        if ($payments->isEmpty()) {
            $payments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->where('collected_by', $handover->collector_id)
                ->whereDate('paid_at', $handover->handover_date->toDateString())
                ->where('payment_method', 'cash')
                ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                ->with('customer:id,customer_id,name,username,phone')
                ->get();
        }

        $voucherData = [
            'id' => $handover->id,
            'handover_no' => $handover->handover_no,
            'handover_date' => $handover->handover_date->translatedFormat('d M Y'),
            'created_at_formatted' => $handover->created_at ? $handover->created_at->translatedFormat('d M Y, h:i A') : $handover->handover_date->translatedFormat('d M Y'),
            'shift_type' => $handover->shift_type,
            'shift_label' => $handover->shift_label,
            'system_collected_amount' => (float) $handover->system_collected_amount,
            'system_collected_formatted' => $currencySymbol . number_format($handover->system_collected_amount, 2),
            'handed_over_amount' => (float) $handover->handed_over_amount,
            'handed_over_formatted' => $currencySymbol . number_format($handover->handed_over_amount, 2),
            'remaining_amount' => max(0, (float) ($handover->system_collected_amount > 0 ? $handover->system_collected_amount : $handover->handed_over_amount) - (float) $handover->handed_over_amount),
            'remaining_formatted' => $currencySymbol . number_format(max(0, (float) ($handover->system_collected_amount > 0 ? $handover->system_collected_amount : $handover->handed_over_amount) - (float) $handover->handed_over_amount), 2),
            'shortage_amount' => (float) $handover->shortage_amount,
            'shortage_formatted' => $currencySymbol . number_format($handover->shortage_amount, 2),
            'excess_amount' => (float) $handover->excess_amount,
            'excess_formatted' => $currencySymbol . number_format($handover->excess_amount, 2),
            'digital_collected_amount' => (float) $handover->digital_collected_amount,
            'digital_collected_formatted' => $currencySymbol . number_format($handover->digital_collected_amount, 2),
            'total_receipts_count' => (int) $handover->total_receipts_count,
            'status' => $handover->status,
            'status_badge' => $handover->status_badge,
            'discrepancy_badge' => $handover->discrepancy_badge,
            'notes' => $handover->notes ?: __('Daily collection closing handover'),
            'manager_remarks' => $handover->manager_remarks,
            'verified_at' => $handover->verified_at ? $handover->verified_at->translatedFormat('d M Y, h:i A') : null,
            'collector' => [
                'id' => $handover->collector?->id,
                'name' => $handover->collector?->name ?? __('Staff Collector'),
                'email' => $handover->collector?->email ?? '--',
                'partner_name' => $handover->collector?->reseller?->name ?? __('ISP Core (HQ)'),
            ],
            'partner_name' => $handover->collector?->reseller?->name ?? __('ISP Core (HQ)'),
            'verifier' => $handover->verifier ? [
                'id' => $handover->verifier->id,
                'name' => $handover->verifier->name,
                'email' => $handover->verifier->email,
            ] : null,
            'company' => [
                'name' => $tenant->company_name ?: ($tenant->name ?? __('ISP Management Portal')),
                'phone' => $tenant->phone ?: ($tenant->support_phone ?? '01700-000000'),
                'address' => $tenant->address ?: __('Head Office, Internet Service Provider'),
            ],
            'receipts' => $payments->map(function ($p) use ($currencySymbol) {
                return [
                    'id' => $p->id,
                    'invoice_no' => $p->invoice_no,
                    'customer_name' => $p->customer?->name ?? __('N/A'),
                    'customer_code' => $p->customer?->customer_id ?? 'SO1000',
                    'amount' => (float) $p->amount,
                    'amount_formatted' => $currencySymbol . number_format($p->amount, 2),
                    'payment_method' => $p->payment_method,
                    'paid_time' => $p->paid_at ? $p->paid_at->translatedFormat('h:i A') : '--',
                ];
            })->values(),
        ];

        return response()->json([
            'success' => true,
            'handover' => $voucherData,
        ]);
    }

    /**
     * Verify and Approve Handover Voucher into Vault.
     */
    public function approveHandover(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $authUser = Auth::user();
        if (!$authUser->isOwner() && !$authUser->isIspAdmin()) {
            abort(403, __('Unauthorized. Only admins can approve handovers.'));
        }

        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($handover->status === 'approved') {
            return $request->wantsJson() 
                ? response()->json(['success' => false, 'message' => __('Voucher already approved.')], 422)
                : back()->with('error', __('Voucher already approved.'));
        }

        $remarks = $request->input('manager_remarks', __('Verified physical cash and approved into vault.'));
        $currencySymbol = $tenant?->currency_symbol ?? '৳';
        $formattedAmount = $currencySymbol . number_format($handover->handed_over_amount, 2);

        DB::beginTransaction();
        try {
            $handover->status = 'approved';
            $handover->verified_by = $authUser->id;
            $handover->verified_at = Carbon::now();
            $handover->manager_remarks = $remarks;
            $handover->save();

            // Ensure constituent payments are locked
            if (Schema::hasColumn('tenant_customer_payments', 'handover_id') && $handover->payments()->count() === 0) {
                TenantCustomerPayment::where('tenant_id', $tenant->id)
                    ->where('collected_by', $handover->collector_id)
                    ->whereDate('paid_at', $handover->handover_date->toDateString())
                    ->where('payment_method', 'cash')
                    ->whereNull('handover_id')
                    ->whereNotIn('status', ['void', 'refunded', 'cancelled'])
                    ->update(['handover_id' => $handover->id]);
            }

            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'CASH_HANDOVER_APPROVED',
                'description' => __("Approved cash handover voucher :no (:amount) for collector ':name'.", [
                    'no' => $handover->handover_no,
                    'amount' => $formattedAmount,
                    'name' => $handover->collector?->name ?? __('Staff Collector'),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'handover_id' => $handover->id,
                    'handover_no' => $handover->handover_no,
                    'collector_id' => $handover->collector_id,
                    'handed_over_amount' => (float) $handover->handed_over_amount,
                ],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $msg = __('Cash handover voucher :no (:amount) approved successfully and transferred into vault.', [
            'no' => $handover->handover_no,
            'amount' => $formattedAmount,
        ]);

        return $request->wantsJson() 
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Edit / Update Handover Voucher details.
     */
    public function updateHandover(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $authUser = Auth::user();
        $isAdmin = $authUser->isOwner() || $authUser->isIspAdmin();
        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        if (!$isAdmin && $handover->collector_id !== $authUser->id) {
            abort(403, __('Unauthorized to edit this handover.'));
        }

        $validated = $request->validate([
            'handed_over_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'shift_type' => 'nullable|string|in:daily,morning,evening,night,full_day',
        ]);

        $newAmount = (float) $validated['handed_over_amount'];
        $systemCash = (float) $handover->system_collected_amount;
        $shortage = max(0, $systemCash - $newAmount);
        $excess = max(0, $newAmount - $systemCash);

        $handover->handed_over_amount = $newAmount;
        $handover->shortage_amount = $shortage;
        $handover->excess_amount = $excess;
        if (isset($validated['notes'])) {
            $handover->notes = $validated['notes'];
        }
        if (!empty($validated['shift_type'])) {
            $handover->shift_type = $validated['shift_type'];
        }
        $handover->save();

        $currencySymbol = $tenant?->currency_symbol ?? '৳';
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'App\Models\User',
            'actor_id' => $authUser->id,
            'actor_name' => $authUser->name,
            'event_type' => 'CASH_HANDOVER_UPDATED',
            'description' => __('Updated cash handover voucher :no amount to :amount', [
                'no' => $handover->handover_no,
                'amount' => $currencySymbol . number_format($newAmount, 2),
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'handover_id' => $handover->id,
                'handed_over_amount' => $newAmount,
                'shortage_amount' => $shortage,
                'excess_amount' => $excess,
            ],
        ]);

        $msg = __('Cash handover voucher :no updated successfully.', ['no' => $handover->handover_no]);
        return $request->wantsJson() 
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    /**
     * Delete / Cancel Handover Voucher and unlock constituent payments.
     */
    public function deleteHandover(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $authUser = Auth::user();
        if (!$authUser->isOwner() && !$authUser->isIspAdmin()) {
            abort(403, __('Unauthorized. Only admins can delete handover records.'));
        }

        $tenant = $this->getTenant();
        $handover = TenantDailyCashHandover::where('tenant_id', $tenant->id)->findOrFail($id);

        DB::beginTransaction();
        try {
            // Unlock constituent payments
            if (Schema::hasColumn('tenant_customer_payments', 'handover_id')) {
                TenantCustomerPayment::where('handover_id', $handover->id)
                    ->update(['handover_id' => null]);
            }

            $handoverNo = $handover->handover_no;
            $handover->delete();

            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'App\Models\User',
                'actor_id' => $authUser->id,
                'actor_name' => $authUser->name,
                'event_type' => 'CASH_HANDOVER_DELETED',
                'description' => __('Deleted cash handover voucher :no and unlocked constituent cash payments.', ['no' => $handoverNo]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $msg = __('Cash handover voucher :no deleted successfully and payments unlocked.', ['no' => $handoverNo]);
        return $request->wantsJson() 
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
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

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

        return view('tenant.collector.collect', compact(
            'tenant',
            'authUser',
            'customer',
            'recentPayments',
            'unpaidInvoices',
            'isOnline',
            'currencySymbol'
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

        $currencySymbol = $tenant?->currency_symbol ?? '৳';

        return view('tenant.collector.thermal_receipt', compact('tenant', 'payment', 'currencySymbol'));
    }
}
