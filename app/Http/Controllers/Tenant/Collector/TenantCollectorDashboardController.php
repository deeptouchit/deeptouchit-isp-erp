<?php

namespace App\Http\Controllers\Tenant\Collector;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
use App\Models\TenantInternetPackage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $perPage = $request->input('per_page', 20);

        // 2. Base Query
        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->orWhereIn('status', ['due', 'expired']);
            })
            ->with(['package', 'zone']);

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

        // Zone filter
        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', $zoneFilter);
        }

        // Package filter
        if ($packageFilter !== 'all' && !empty($packageFilter)) {
            $query->where('internet_package_id', $packageFilter);
        }

        // Due status filter
        if ($dueStatusFilter === 'expired_only') {
            $query->where('status', 'expired');
        } elseif ($dueStatusFilter === 'due_only') {
            $query->where('status', 'due');
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

        // 3. Pagination
        $query->orderByDesc('due_amount');
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

        // 5. High-Performance KPI Strip
        $baseCustomers = TenantCustomer::where('tenant_id', $tenantId);
        if ($isCollector && isset($hasSpecificAssignments) && $hasSpecificAssignments) {
            $baseCustomers->where('assigned_collector_id', $collectorId);
        }

        $totalDueCustomers = (clone $baseCustomers)->where(function ($q) {
            $q->where('due_amount', '>', 0)->orWhereIn('status', ['due', 'expired']);
        })->count();
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

        $expiredCustomers = (clone $baseCustomers)->where('status', 'expired')->count();
        $activeSubscribers = (clone $baseCustomers)->where('status', 'active')->count();

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
            'stats',
            'search',
            'zoneFilter',
            'packageFilter',
            'dueStatusFilter',
            'collectorFilter',
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

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->orWhereIn('status', ['due', 'expired']);
            })
            ->with(['package', 'zone']);

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

        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', $zoneFilter);
        }

        if ($packageFilter !== 'all' && !empty($packageFilter)) {
            $query->where('internet_package_id', $packageFilter);
        }

        if ($dueStatusFilter === 'expired_only') {
            $query->where('status', 'expired');
        } elseif ($dueStatusFilter === 'due_only') {
            $query->where('status', 'due');
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

        $customers = $query->orderByDesc('due_amount')->get();
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

        $totalCashCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $totalApprovedHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->whereIn('status', ['approved', 'verified'])
            ->sum('handed_over_amount');

        $pendingSubmissionHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->where('status', 'pending')
            ->sum('handed_over_amount');

        $availableCashInHand = max(0, $totalCashCollected - $totalApprovedHandovers);

        $stats = [
            'available_cash_in_hand' => $availableCashInHand,
            'pending_approval' => $pendingSubmissionHandovers,
            'total_approved_handed' => $totalApprovedHandovers,
            'total_cash_collected' => $totalCashCollected,
            'total_handovers_count' => TenantDailyCashHandover::where('tenant_id', $tenantId)->where('collector_id', $collectorId)->count(),
            'today_collected' => (float) TenantCustomerPayment::where('tenant_id', $tenantId)->where('collected_by', $collectorId)->whereDate('created_at', today())->sum('amount'),
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

        $count = TenantDailyCashHandover::where('tenant_id', $tenant->id)->count() + 1;
        $handoverNo = 'HND-' . date('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        TenantDailyCashHandover::create([
            'tenant_id' => $tenant->id,
            'handover_no' => $handoverNo,
            'collector_id' => $authUser->id,
            'handover_date' => now()->toDateString(),
            'system_collected_amount' => (float) $request->amount,
            'handed_over_amount' => (float) $request->amount,
            'notes' => $request->notes ?: "HQ cash handover by {$authUser->name}",
            'status' => 'pending',
        ]);

        return back()->with('success', "দৈনিক ক্যাশ হ্যান্ডওভার রিকোয়েস্ট (" . number_format((float)$request->amount, 2) . " ৳) সফলভাবে জমা দেওয়া হয়েছে।");
    }
}
