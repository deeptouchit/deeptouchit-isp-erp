<?php

namespace App\Http\Controllers\Tenant\Collector;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantDailyCashHandover;
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
     * Display Area Due Customers for Collector.
     */
    public function dueCustomers(Request $request): View
    {
        $authUser = Auth::user();
        $tenant = $this->getTenant();
        $tenantId = $tenant?->id;
        $collectorId = $authUser->id;

        $search = trim($request->input('search', ''));
        $zoneFilter = $request->input('zone_id', 'all');
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = TenantCustomer::where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('due_amount', '>', 0)
                  ->orWhereIn('status', ['due', 'expired']);
            })
            ->with(['package', 'zone']);

        if ($zoneFilter !== 'all' && !empty($zoneFilter)) {
            $query->where('zone_id', $zoneFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('due_amount')->paginate($perPage)->withQueryString();

        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->get();

        $baseCustomers = TenantCustomer::where('tenant_id', $tenantId);
        $totalDueCustomers = (clone $baseCustomers)->where(function ($q) {
            $q->where('due_amount', '>', 0)->orWhereIn('status', ['due', 'expired']);
        })->count();
        $totalDueAmount = (float) (clone $baseCustomers)->sum('due_amount');

        $todayCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->whereDate('created_at', today())
            ->sum('amount');

        $totalCashCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('collected_by', $collectorId)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $totalApprovedHandovers = (float) TenantDailyCashHandover::where('tenant_id', $tenantId)
            ->where('collector_id', $collectorId)
            ->whereIn('status', ['approved', 'verified'])
            ->sum('handed_over_amount');

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
            'stats',
            'search',
            'zoneFilter',
            'perPage',
            'currencySymbol'
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
