<?php

namespace App\Http\Controllers\Tenant\Report;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantReseller;
use App\Models\TenantResellerInvoice;
use App\Models\TenantExpenseTransaction;
use App\Models\TenantInternetPackage;
use App\Models\TenantCoverageZone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCollectionReportController extends Controller
{
    /**
     * Get Current Active Tenant Workspace
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : (auth()->user()?->tenant ?? Tenant::first());

        if (!$tenant) {
            abort(403, 'Tenant context not found. Please log in.');
        }

        return $tenant;
    }

    /**
     * Display the Consolidated Collection & Due Breakdown Analytics Report
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $preset = $request->get('preset', 'this_month');
        $startDate = $request->get('start_date') ?: $request->get('date_from');
        $endDate = $request->get('end_date') ?: $request->get('date_to');
        $activeTab = $request->get('tab', 'zones'); // zones, collectors, packages, resellers, gateways, customers
        $stream = $request->get('stream', 'all'); // all, retail, wholesale, gateway, direct
        $zoneId = $request->get('zone_id', 'all');
        $collectorId = $request->get('collector_id', 'all');
        $packageId = $request->get('package_id', 'all');
        $statusFilter = $request->get('status', 'all'); // all, due, active, suspended, disconnected
        $search = trim($request->get('search', ''));
        $perPage = (int) $request->get('per_page', 20);

        // 1. Resolve Active Date Range
        [$from, $to] = $this->resolveDateRanges($preset, $startDate, $endDate);

        // 2. Load Filter Dropdowns
        $allZones = TenantCoverageZone::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();
        $allCollectors = User::where('tenant_id', $tenant->id)
            ->where(function($q) {
                $q->where('role', 'like', '%collector%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->orderBy('name')
            ->get();

        // 3. Consolidated Multi-Stream Accounting
        // A. Retail Subscriber Invoices / Bills
        $retailInvoiceQuery = TenantCustomerInvoice::where('tenant_id', $tenant->id)
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
        if ($zoneId !== 'all' && !empty($zoneId)) {
            $retailInvoiceQuery->whereHas('customer', fn($q) => $q->where('zone_id', (int)$zoneId));
        }
        if ($packageId !== 'all' && !empty($packageId)) {
            $retailInvoiceQuery->where('package_id', (int)$packageId);
        }
        $retailBilled = (float) $retailInvoiceQuery->sum('total_payable');
        if ($retailBilled == 0) {
            $retailBilled = (float) TenantCustomer::where('tenant_id', $tenant->id)
                ->whereIn('status', ['active', 'online', 'due'])
                ->sum('monthly_bill');
        }

        // Retail Payments
        $retailPaymentQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to])
            ->where('status', '!=', 'void');
        if ($zoneId !== 'all' && !empty($zoneId)) {
            $retailPaymentQuery->whereHas('customer', fn($q) => $q->where('zone_id', (int)$zoneId));
        }
        if ($packageId !== 'all' && !empty($packageId)) {
            $retailPaymentQuery->whereHas('customer', fn($q) => $q->where('package_id', (int)$packageId));
        }
        if ($collectorId !== 'all' && !empty($collectorId)) {
            $retailPaymentQuery->where('collected_by', (int)$collectorId);
        }
        $retailCollected = (float) $retailPaymentQuery->sum('amount');
        $retailDiscount = (float) $retailPaymentQuery->sum('discount');

        // Retail Outstanding Dues
        $retailDueQuery = TenantCustomer::where('tenant_id', $tenant->id);
        if ($zoneId !== 'all' && !empty($zoneId)) {
            $retailDueQuery->where('zone_id', (int)$zoneId);
        }
        if ($packageId !== 'all' && !empty($packageId)) {
            $retailDueQuery->where('package_id', (int)$packageId);
        }
        if ($collectorId !== 'all' && !empty($collectorId)) {
            $retailDueQuery->where('assigned_collector_id', (int)$collectorId);
        }
        $retailDue = (float) $retailDueQuery->sum('due_amount');
        $retailDueSubscribersCount = (int) $retailDueQuery->where('due_amount', '>', 0)->count();

        // B. Wholesale Sub-ISP Resellers Invoices & Collections
        $wholesaleBilled = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where(function($q) use ($from, $to) {
                $q->whereBetween('billing_month', [$from->toDateString(), $to->toDateString()])
                  ->orWhereBetween('created_at', [$from, $to]);
            })
            ->sum('amount');

        $wholesaleCollected = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['PAID', 'PARTIAL'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('paid_amount');

        $wholesaleDue = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('payment_status', '!=', 'PAID')
            ->sum('due_amount');

        $wholesaleRecharges = (float) DB::table('tenant_reseller_recharges')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'APPROVED')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // C. Direct Incomes
        $directIncome = (float) TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        // D. Digital Gateway Collections (bKash, Nagad, Rocket, SSL, etc.)
        $gatewayMethods = ['bkash', 'nagad', 'rocket', 'sslcommerz', 'shurjopay', 'upay', 'aamarpay', 'stripe', 'online', 'pos', 'bkash_merchant', 'nagad_merchant', 'online_pgw'];
        $gatewayCollected = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereIn(DB::raw('LOWER(payment_method)'), $gatewayMethods)
            ->whereBetween('paid_at', [$from, $to])
            ->where('status', '!=', 'void')
            ->sum('amount');

        // Scope Adjustments based on $stream filter
        if ($stream === 'retail') {
            $totalBilled = $retailBilled;
            $totalCollected = $retailCollected;
            $totalDue = $retailDue;
            $totalDiscount = $retailDiscount;
            $dueSubscribersCount = $retailDueSubscribersCount;
        } elseif ($stream === 'wholesale') {
            $totalBilled = $wholesaleBilled;
            $totalCollected = $wholesaleCollected + $wholesaleRecharges;
            $totalDue = $wholesaleDue;
            $totalDiscount = 0.0;
            $dueSubscribersCount = TenantResellerInvoice::where('tenant_id', $tenant->id)->where('payment_status', '!=', 'PAID')->distinct('reseller_id')->count('reseller_id');
        } elseif ($stream === 'gateway') {
            $totalBilled = $retailBilled;
            $totalCollected = $gatewayCollected;
            $totalDue = $retailDue;
            $totalDiscount = $retailDiscount;
            $dueSubscribersCount = $retailDueSubscribersCount;
        } elseif ($stream === 'direct') {
            $totalBilled = $directIncome;
            $totalCollected = $directIncome;
            $totalDue = 0.0;
            $totalDiscount = 0.0;
            $dueSubscribersCount = 0;
        } else {
            // Combined All Streams
            $totalBilled = $retailBilled + $wholesaleBilled + $directIncome;
            $totalCollected = $retailCollected + $wholesaleCollected + $wholesaleRecharges + $directIncome;
            $totalDue = $retailDue + $wholesaleDue;
            $totalDiscount = $retailDiscount;
            $dueSubscribersCount = $retailDueSubscribersCount;
        }

        // Collection Efficiency Rate %
        $totalReceivable = $totalCollected + $totalDue;
        $collectionEfficiency = $totalReceivable > 0 ? round(($totalCollected / $totalReceivable) * 100, 1) : 0;

        // 4. Tab 1: Zone Breakdown Matrix
        $zoneMatrix = $this->buildZoneBreakdown($tenant->id, $from, $to);

        // 5. Tab 2: Collector Staff Performance Breakdown
        $collectorMatrix = $this->buildCollectorBreakdown($tenant->id, $from, $to);

        // 6. Tab 3: Package Breakdown Matrix
        $packageMatrix = $this->buildPackageBreakdown($tenant->id, $from, $to);

        // 7. Tab 4: Sub-ISP Wholesale Resellers Matrix
        $wholesaleMatrix = $this->buildWholesaleBreakdown($tenant->id, $from, $to);

        // 8. Tab 5: Payment Gateways Matrix
        $gatewayMatrix = $this->buildGatewayBreakdown($tenant->id, $from, $to);

        // 9. Tab 6: Customer Detailed Dues & Collection Ledger
        $customersQuery = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['coverageZone', 'package', 'collector']);

        if (!empty($search)) {
            $customersQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($zoneId !== 'all' && !empty($zoneId)) {
            $customersQuery->where('zone_id', (int)$zoneId);
        }
        if ($packageId !== 'all' && !empty($packageId)) {
            $customersQuery->where('package_id', (int)$packageId);
        }
        if ($collectorId !== 'all' && !empty($collectorId)) {
            $customersQuery->where('assigned_collector_id', (int)$collectorId);
        }
        if ($statusFilter === 'due') {
            $customersQuery->where('due_amount', '>', 0);
        } elseif ($statusFilter !== 'all' && !empty($statusFilter)) {
            $customersQuery->where('status', $statusFilter);
        }

        $customerRecords = $customersQuery->orderByDesc('due_amount')->paginate($perPage)->withQueryString();

        // 10. Aging Dues Analysis (0-30, 31-60, 61-90, 90+ days)
        $agingAnalysis = $this->calculateAgingDues($tenant->id);

        // 11. Payment Channels Breakdown
        $paymentChannels = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to])
            ->where('status', '!=', 'void')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // 12. Top 5 Defaulters
        $topDefaulters = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('due_amount', '>', 0)
            ->with(['coverageZone', 'package'])
            ->orderByDesc('due_amount')
            ->take(5)
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.reports.collection', compact(
            'tenant',
            'preset',
            'startDate',
            'endDate',
            'stream',
            'activeTab',
            'zoneId',
            'collectorId',
            'packageId',
            'statusFilter',
            'search',
            'perPage',
            'from',
            'to',
            'allZones',
            'allPackages',
            'allCollectors',
            'totalBilled',
            'totalCollected',
            'totalDue',
            'totalDiscount',
            'collectionEfficiency',
            'dueSubscribersCount',
            'retailCollected',
            'wholesaleCollected',
            'gatewayCollected',
            'directIncome',
            'zoneMatrix',
            'collectorMatrix',
            'packageMatrix',
            'wholesaleMatrix',
            'gatewayMatrix',
            'customerRecords',
            'agingAnalysis',
            'paymentChannels',
            'topDefaulters',
            'currencySymbol'
        ));
    }

    /**
     * Standalone Full-Page Print Statement View for Collection & Due Report
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $preset = $request->get('preset', 'this_month');
        $startDate = $request->get('start_date') ?: $request->get('date_from');
        $endDate = $request->get('end_date') ?: $request->get('date_to');

        [$from, $to] = $this->resolveDateRanges($preset, $startDate, $endDate);

        $retailCollected = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to])
            ->where('status', '!=', 'void')
            ->sum('amount');

        $wholesaleCollected = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['PAID', 'PARTIAL'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('paid_amount');

        $directIncome = (float) TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalCollected = $retailCollected + $wholesaleCollected + $directIncome;

        $retailDue = (float) TenantCustomer::where('tenant_id', $tenant->id)->sum('due_amount');
        $wholesaleDue = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)->where('payment_status', '!=', 'PAID')->sum('due_amount');
        $totalDue = $retailDue + $wholesaleDue;

        $dueSubscribersCount = (int) TenantCustomer::where('tenant_id', $tenant->id)->where('due_amount', '>', 0)->count();
        $totalReceivable = $totalCollected + $totalDue;
        $collectionEfficiency = $totalReceivable > 0 ? round(($totalCollected / $totalReceivable) * 100, 1) : 0;

        $zoneMatrix = $this->buildZoneBreakdown($tenant->id, $from, $to);
        $collectorMatrix = $this->buildCollectorBreakdown($tenant->id, $from, $to);
        $packageMatrix = $this->buildPackageBreakdown($tenant->id, $from, $to);
        $wholesaleMatrix = $this->buildWholesaleBreakdown($tenant->id, $from, $to);
        $gatewayMatrix = $this->buildGatewayBreakdown($tenant->id, $from, $to);
        $agingAnalysis = $this->calculateAgingDues($tenant->id);
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.reports.collection_print', compact(
            'tenant',
            'preset',
            'from',
            'to',
            'totalCollected',
            'totalDue',
            'dueSubscribersCount',
            'collectionEfficiency',
            'zoneMatrix',
            'collectorMatrix',
            'packageMatrix',
            'wholesaleMatrix',
            'gatewayMatrix',
            'agingAnalysis',
            'currencySymbol'
        ));
    }

    /**
     * Streamed CSV / Excel Export of Collection & Due Records
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $filename = 'isp_collection_due_report_' . date('Y_m_d_His') . '.csv';

        $customers = TenantCustomer::where('tenant_id', $tenant->id)
            ->with(['coverageZone', 'package', 'collector'])
            ->orderByDesc('due_amount')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($customers, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Title block
            fputcsv($handle, [$tenant->name . ' - Collection & Due Audit Statement']);
            fputcsv($handle, ['Generated At', date('d M Y, h:i A')]);
            fputcsv($handle, []);

            // Header row
            fputcsv($handle, [
                'Customer ID',
                'Subscriber Name',
                'PPPoE Username',
                'Phone',
                'Coverage Zone',
                'Package',
                'Monthly Bill',
                'Outstanding Due',
                'Wallet Balance',
                'Status',
                'Assigned Collector',
            ]);

            foreach ($customers as $c) {
                fputcsv($handle, [
                    $c->customer_id ?? ('CUST-' . $c->id),
                    $c->name,
                    $c->username,
                    $c->phone,
                    $c->coverageZone?->name ?? 'Default Area',
                    $c->package?->name ?? $c->package_name,
                    number_format($c->monthly_bill, 2, '.', ''),
                    number_format($c->due_amount, 2, '.', ''),
                    number_format($c->wallet_balance, 2, '.', ''),
                    strtoupper($c->status),
                    $c->collector?->name ?? 'Unassigned',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Build Zone-wise Aggregation Matrix
     */
    protected function buildZoneBreakdown(int $tenantId, Carbon $from, Carbon $to): array
    {
        $zones = TenantCoverageZone::where('tenant_id', $tenantId)->get();
        $matrix = [];

        foreach ($zones as $zone) {
            $customers = TenantCustomer::where('tenant_id', $tenantId)->where('zone_id', $zone->id)->get();
            $subsCount = $customers->count();
            $activeCount = $customers->whereIn('status', ['active', 'online'])->count();
            $totalBilled = (float) $customers->sum('monthly_bill');
            $totalDue = (float) $customers->sum('due_amount');

            $totalCollected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
                ->whereBetween('paid_at', [$from, $to])
                ->where('status', '!=', 'void')
                ->whereHas('customer', fn($q) => $q->where('zone_id', $zone->id))
                ->sum('amount');

            $receivable = $totalCollected + $totalDue;
            $effRate = $receivable > 0 ? round(($totalCollected / $receivable) * 100, 1) : 0;

            $matrix[] = [
                'id' => $zone->id,
                'name' => $zone->name,
                'code' => $zone->code ?: ('Z-' . $zone->id),
                'subscribers_count' => $subsCount,
                'active_count' => $activeCount,
                'total_billed' => $totalBilled,
                'total_collected' => $totalCollected,
                'total_due' => $totalDue,
                'efficiency_pct' => $effRate,
            ];
        }

        // Sort by Due Descending
        usort($matrix, fn($a, $b) => $b['total_due'] <=> $a['total_due']);

        return $matrix;
    }

    /**
     * Build Collector Staff Aggregation Matrix
     */
    protected function buildCollectorBreakdown(int $tenantId, Carbon $from, Carbon $to): array
    {
        $collectors = User::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('role', 'like', '%collector%')
                  ->orWhere('role', 'like', '%manager%')
                  ->orWhere('role', 'isp_admin');
            })
            ->get();

        $matrix = [];

        foreach ($collectors as $user) {
            $assignedCustomers = TenantCustomer::where('tenant_id', $tenantId)->where('assigned_collector_id', $user->id)->get();
            $assignedCount = $assignedCustomers->count();
            $targetDue = (float) $assignedCustomers->sum('due_amount');
            $targetBilled = (float) $assignedCustomers->sum('monthly_bill');

            $collected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
                ->where('collected_by', $user->id)
                ->whereBetween('paid_at', [$from, $to])
                ->where('status', '!=', 'void')
                ->sum('amount');

            $targetTotal = $collected + $targetDue;
            $targetRate = $targetTotal > 0 ? round(($collected / $targetTotal) * 100, 1) : 0;

            $matrix[] = [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone ?? $user->email,
                'role' => ucwords(str_replace('_', ' ', $user->role)),
                'assigned_count' => $assignedCount,
                'target_billed' => $targetBilled,
                'total_collected' => $collected,
                'outstanding_due' => $targetDue,
                'efficiency_pct' => $targetRate,
            ];
        }

        // Sort by Collected Descending
        usort($matrix, fn($a, $b) => $b['total_collected'] <=> $a['total_collected']);

        return $matrix;
    }

    /**
     * Build Package-wise Aggregation Matrix
     */
    protected function buildPackageBreakdown(int $tenantId, Carbon $from, Carbon $to): array
    {
        $packages = TenantInternetPackage::where('tenant_id', $tenantId)->get();
        $matrix = [];

        foreach ($packages as $pkg) {
            $customers = TenantCustomer::where('tenant_id', $tenantId)->where('package_id', $pkg->id)->get();
            $subsCount = $customers->count();
            $totalBilled = (float) $customers->sum('monthly_bill');
            $totalDue = (float) $customers->sum('due_amount');

            $collected = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
                ->whereBetween('paid_at', [$from, $to])
                ->where('status', '!=', 'void')
                ->whereHas('customer', fn($q) => $q->where('package_id', $pkg->id))
                ->sum('amount');

            $receivable = $collected + $totalDue;
            $effRate = $receivable > 0 ? round(($collected / $receivable) * 100, 1) : 0;

            $speedText = ($pkg->download_speed ? $pkg->download_speed . 'M' : '') ?: ($pkg->name ?? 'Standard');

            $matrix[] = [
                'id' => $pkg->id,
                'name' => $pkg->package_name ?: ($pkg->name ?: 'Standard Package'),
                'speed' => $speedText,
                'price' => (float)$pkg->price,
                'subscribers_count' => $subsCount,
                'total_billed' => $totalBilled,
                'total_collected' => $collected,
                'total_due' => $totalDue,
                'efficiency_pct' => $effRate,
            ];
        }

        // Sort by Total Due Descending
        usort($matrix, fn($a, $b) => $b['total_due'] <=> $a['total_due']);

        return $matrix;
    }

    /**
     * Build Sub-ISP Wholesale Reseller Breakdown Matrix
     */
    protected function buildWholesaleBreakdown(int $tenantId, Carbon $from, Carbon $to): array
    {
        $resellers = TenantReseller::where('tenant_id', $tenantId)->get();
        $matrix = [];

        foreach ($resellers as $reseller) {
            $invoices = TenantResellerInvoice::where('tenant_id', $tenantId)->where('reseller_id', $reseller->id)->get();
            
            $periodBilled = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
                ->where('reseller_id', $reseller->id)
                ->where(function($q) use ($from, $to) {
                    $q->whereBetween('billing_month', [$from->toDateString(), $to->toDateString()])
                      ->orWhereBetween('created_at', [$from, $to]);
                })
                ->sum('amount');

            $periodCollected = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
                ->where('reseller_id', $reseller->id)
                ->whereBetween('paid_at', [$from, $to])
                ->whereIn('payment_status', ['PAID', 'PARTIAL'])
                ->sum('paid_amount');

            $periodRecharges = (float) DB::table('tenant_reseller_recharges')
                ->where('tenant_id', $tenantId)
                ->where('reseller_id', $reseller->id)
                ->where('status', 'APPROVED')
                ->whereBetween('created_at', [$from, $to])
                ->sum('amount');

            $totalCollected = $periodCollected + $periodRecharges;
            $outstandingDue = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
                ->where('reseller_id', $reseller->id)
                ->where('payment_status', '!=', 'PAID')
                ->sum('due_amount');

            $receivable = $totalCollected + $outstandingDue;
            $effRate = $receivable > 0 ? round(($totalCollected / $receivable) * 100, 1) : 0;

            $matrix[] = [
                'id' => $reseller->id,
                'name' => $reseller->name,
                'code' => $reseller->code ?: ('RES-' . $reseller->id),
                'contact_person' => $reseller->contact_person ?: $reseller->name,
                'mobile' => $reseller->mobile,
                'billing_type' => strtoupper($reseller->billing_type ?? 'PREPAID'),
                'wallet_balance' => (float) $reseller->wallet_balance,
                'credit_limit' => (float) $reseller->credit_limit,
                'period_billed' => $periodBilled,
                'period_collected' => $totalCollected,
                'outstanding_due' => $outstandingDue,
                'efficiency_pct' => $effRate,
                'status' => strtoupper($reseller->status ?? 'ACTIVE'),
            ];
        }

        usort($matrix, fn($a, $b) => $b['outstanding_due'] <=> $a['outstanding_due']);

        return $matrix;
    }

    /**
     * Build Digital Payment Gateways Breakdown Matrix
     */
    protected function buildGatewayBreakdown(int $tenantId, Carbon $from, Carbon $to): array
    {
        $gatewayRows = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$from, $to])
            ->where('status', '!=', 'void')
            ->select('payment_method', DB::raw('count(*) as txn_count'), DB::raw('sum(amount) as total_amount'))
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        $grandTotal = (float) $gatewayRows->sum('total_amount');
        $matrix = [];

        foreach ($gatewayRows as $row) {
            $method = strtolower($row->payment_method ?? 'cash');
            $sharePct = $grandTotal > 0 ? round(($row->total_amount / $grandTotal) * 100, 1) : 0;

            $matrix[] = [
                'method_key' => $method,
                'channel_name' => ucwords(str_replace('_', ' ', $row->payment_method ?: 'Cash')),
                'txn_count' => (int) $row->txn_count,
                'total_amount' => (float) $row->total_amount,
                'share_pct' => $sharePct,
                'is_gateway' => in_array($method, ['bkash', 'nagad', 'rocket', 'sslcommerz', 'shurjopay', 'upay', 'aamarpay', 'stripe', 'online', 'pos', 'bkash_merchant', 'nagad_merchant', 'online_pgw']),
            ];
        }

        return $matrix;
    }

    /**
     * Calculate Aging Dues (0-30, 31-60, 61-90, 90+ days)
     */
    protected function calculateAgingDues(int $tenantId): array
    {
        $customersWithDue = TenantCustomer::where('tenant_id', $tenantId)
            ->where('due_amount', '>', 0)
            ->get();

        $aging = [
            'current' => ['label' => '0-30 Days (Current)', 'amount' => 0.0, 'count' => 0, 'color' => 'emerald'],
            'overdue_30' => ['label' => '31-60 Days (Overdue)', 'amount' => 0.0, 'count' => 0, 'color' => 'amber'],
            'critical_60' => ['label' => '61-90 Days (Critical)', 'amount' => 0.0, 'count' => 0, 'color' => 'orange'],
            'bad_debt_90' => ['label' => '90+ Days (High Risk)', 'amount' => 0.0, 'count' => 0, 'color' => 'rose'],
        ];

        $now = Carbon::now();

        foreach ($customersWithDue as $cust) {
            $daysSinceBill = 15; // default estimation
            if ($cust->expiry_date) {
                $daysSinceBill = max(0, $now->diffInDays($cust->expiry_date, false) * -1);
            }

            if ($daysSinceBill <= 30) {
                $aging['current']['amount'] += (float)$cust->due_amount;
                $aging['current']['count']++;
            } elseif ($daysSinceBill <= 60) {
                $aging['overdue_30']['amount'] += (float)$cust->due_amount;
                $aging['overdue_30']['count']++;
            } elseif ($daysSinceBill <= 90) {
                $aging['critical_60']['amount'] += (float)$cust->due_amount;
                $aging['critical_60']['count']++;
            } else {
                $aging['bad_debt_90']['amount'] += (float)$cust->due_amount;
                $aging['bad_debt_90']['count']++;
            }
        }

        $totalAging = array_sum(array_column($aging, 'amount'));
        foreach ($aging as &$bracket) {
            $bracket['percentage'] = $totalAging > 0 ? round(($bracket['amount'] / $totalAging) * 100, 1) : 0;
        }

        return $aging;
    }

    /**
     * Resolve Period Range with all quick presets
     */
    protected function resolveDateRanges(string $preset, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $from = (clone $now)->startOfDay();
                $to = (clone $now)->endOfDay();
                break;

            case 'yesterday':
                $from = (clone $now)->subDay()->startOfDay();
                $to = (clone $now)->subDay()->endOfDay();
                break;

            case 'this_week':
                $from = (clone $now)->startOfWeek(Carbon::SATURDAY);
                $to = (clone $now)->endOfDay();
                break;

            case 'last_month':
                $from = (clone $now)->subMonth()->startOfMonth();
                $to = (clone $now)->subMonth()->endOfMonth();
                break;

            case 'this_quarter':
                $from = (clone $now)->startOfQuarter();
                $to = (clone $now)->endOfQuarter();
                break;

            case 'this_year':
                $from = (clone $now)->startOfYear();
                $to = (clone $now)->endOfYear();
                break;

            case 'last_12_months':
                $from = (clone $now)->subMonths(11)->startOfMonth();
                $to = (clone $now)->endOfMonth();
                break;

            case 'all':
                $from = Carbon::parse('2020-01-01')->startOfDay();
                $to = (clone $now)->endOfDay();
                break;

            case 'custom':
                if ($customStart && $customEnd) {
                    $from = Carbon::parse($customStart)->startOfDay();
                    $to = Carbon::parse($customEnd)->endOfDay();
                } else {
                    $from = (clone $now)->startOfMonth();
                    $to = (clone $now)->endOfMonth();
                }
                break;

            case 'this_month':
            default:
                $from = (clone $now)->startOfMonth();
                $to = (clone $now)->endOfMonth();
                break;
        }

        return [$from, $to];
    }
}
