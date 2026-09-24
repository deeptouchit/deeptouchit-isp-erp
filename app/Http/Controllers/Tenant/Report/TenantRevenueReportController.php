<?php

namespace App\Http\Controllers\Tenant\Report;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantResellerInvoice;
use App\Models\TenantExpenseTransaction;
use App\Models\TenantInternetPackage;
use App\Models\TenantCoverageZone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantRevenueReportController extends Controller
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
     * Display the Executive Revenue & Business Growth Analytics Report
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $preset = $request->get('preset', 'this_month');
        $startDate = $request->get('start_date') ?: $request->get('date_from');
        $endDate = $request->get('end_date') ?: $request->get('date_to');
        $stream = $request->get('stream', 'all'); // all, retail, wholesale, recharge, gateway, direct
        $zoneId = $request->get('zone_id', 'all');
        $packageId = $request->get('package_id', 'all');

        // 1. Resolve Active Date Range
        [$from, $to, $prevFrom, $prevTo] = $this->resolveDateRanges($preset, $startDate, $endDate);

        // 2. Load Filter Dropdowns
        $allZones = TenantCoverageZone::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $allPackages = TenantInternetPackage::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('name')->get();

        // 3. Calculate Period Revenue Streams
        // A. Retail Subscriber Payments
        $retailQuery = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to]);
        if ($zoneId !== 'all' && !empty($zoneId)) {
            $retailQuery->whereHas('customer', fn($q) => $q->where('zone_id', (int)$zoneId));
        }
        if ($packageId !== 'all' && !empty($packageId)) {
            $retailQuery->whereHas('customer', fn($q) => $q->where('package_id', (int)$packageId));
        }
        $periodRetailRevenue = (float) $retailQuery->sum('amount');

        // B. Wholesale Sub-ISP Invoices Paid
        $wholesaleQuery = TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['PAID', 'PARTIAL'])
            ->whereBetween('paid_at', [$from, $to]);
        $periodWholesaleRevenue = (float) $wholesaleQuery->sum('paid_amount');

        // C. Reseller Wallet Recharges
        $periodRechargeRevenue = (float) DB::table('tenant_reseller_recharges')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'APPROVED')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // D. Digital Gateway Channels Revenue
        $gatewayMethods = ['bkash', 'nagad', 'rocket', 'sslcommerz', 'shurjopay', 'upay', 'aamarpay', 'stripe', 'online', 'pos', 'bkash_merchant', 'nagad_merchant', 'online_pgw'];
        $periodGatewayRevenue = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereIn(DB::raw('LOWER(payment_method)'), $gatewayMethods)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        // E. Direct Revenues / Incomes
        $directIncomeQuery = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
        $periodDirectIncome = (float) $directIncomeQuery->sum('amount');

        // Total Gross Revenue for Selected Scope
        if ($stream === 'retail') {
            $totalRevenue = $periodRetailRevenue;
        } elseif ($stream === 'wholesale') {
            $totalRevenue = $periodWholesaleRevenue;
        } elseif ($stream === 'recharge') {
            $totalRevenue = $periodRechargeRevenue;
        } elseif ($stream === 'gateway') {
            $totalRevenue = $periodGatewayRevenue;
        } elseif ($stream === 'direct') {
            $totalRevenue = $periodDirectIncome;
        } else {
            $totalRevenue = $periodRetailRevenue + $periodWholesaleRevenue + $periodDirectIncome;
        }

        // 4. Calculate Operating OPEX / Expenses in Period (General OPEX + Carrier Bandwidth COGS)
        $expenseQuery = TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'EXPENSE')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
        $periodGeneralExpenses = (float) $expenseQuery->sum('amount');

        // Carrier Upstream COGS Cost
        $periodCarrierCost = 0.0;
        if (DB::getSchemaBuilder()->hasTable('tenant_carrier_payments')) {
            $periodCarrierCost = (float) DB::table('tenant_carrier_payments')
                ->where('tenant_id', $tenant->id)
                ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
                ->sum('amount');
        }

        $periodExpenses = $periodGeneralExpenses + $periodCarrierCost;

        // Net Operating Profit
        $netProfit = $totalRevenue - $periodExpenses;
        $profitMarginPct = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // 5. Calculate Previous Period for MoM Growth Rate
        $prevRetail = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$prevFrom, $prevTo])
            ->sum('amount');
        $prevWholesale = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['PAID', 'PARTIAL'])
            ->whereBetween('paid_at', [$prevFrom, $prevTo])
            ->sum('paid_amount');
        $prevDirect = (float) TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$prevFrom->toDateString(), $prevTo->toDateString()])
            ->sum('amount');

        $prevTotalRevenue = $prevRetail + $prevWholesale + $prevDirect;
        $growthRatePct = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;

        // 6. Contracted MRR & ARPU
        $activeSubscribersCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'online'])
            ->count();
        $totalSubscribersCount = TenantCustomer::where('tenant_id', $tenant->id)->count();

        $contractedRetailMrr = (float) TenantCustomer::where('tenant_id', $tenant->id)
            ->whereIn('status', ['active', 'online', 'due'])
            ->sum('monthly_bill');

        $contractedWholesaleMrr = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('billing_month', 'like', Carbon::now()->format('Y-m') . '%')
            ->where('payment_status', '!=', 'CANCELLED')
            ->sum('amount');

        $totalMrr = $contractedRetailMrr + $contractedWholesaleMrr;
        $arpu = $activeSubscribersCount > 0 ? ($contractedRetailMrr / $activeSubscribersCount) : 0;

        // 7. Subscriber Growth Metrics in Period
        $newSubscribersCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $churnedSubscribersCount = TenantCustomer::where('tenant_id', $tenant->id)
            ->where('status', 'disconnected')
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        $netSubscriberGrowth = $newSubscribersCount - $churnedSubscribersCount;

        // 8. Total Outstanding Receivables (Dues)
        $customerDues = (float) TenantCustomer::where('tenant_id', $tenant->id)->sum('due_amount');
        $wholesaleDues = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->where('payment_status', '!=', 'PAID')
            ->where('payment_status', '!=', 'CANCELLED')
            ->sum('due_amount');
        $totalOutstandingDue = $customerDues + $wholesaleDues;

        // 9. Generate 12-Month Historical Growth Comparison Matrix
        $monthlyMatrix = $this->build12MonthMatrix($tenant->id);

        // 10. Top Revenue Generating Internet Packages
        $topPackages = TenantInternetPackage::where('tenant_id', $tenant->id)
            ->withCount(['customers' => function($q) {
                $q->whereIn('status', ['active', 'online']);
            }])
            ->get()
            ->map(function($pkg) use ($contractedRetailMrr) {
                $pkgRevenue = (float) ($pkg->price * $pkg->customers_count);
                $sharePct = $contractedRetailMrr > 0 ? ($pkgRevenue / $contractedRetailMrr) * 100 : 0;
                $speedText = ($pkg->download_speed ? $pkg->download_speed . 'M' : '') ?: ($pkg->name ?? 'Standard');
                return [
                    'id' => $pkg->id,
                    'name' => $pkg->package_name ?: ($pkg->name ?: 'Standard Package'),
                    'speed' => $speedText,
                    'price' => (float)$pkg->price,
                    'subscribers_count' => $pkg->customers_count,
                    'monthly_revenue' => $pkgRevenue,
                    'share_pct' => round($sharePct, 1),
                ];
            })
            ->sortByDesc('monthly_revenue')
            ->values()
            ->take(6);

        // 11. Payment Channels Share
        $paymentChannels = TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to])
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // 12. Top Revenue Zones
        $topZones = DB::table('tenant_coverage_zones')
            ->where('tenant_coverage_zones.tenant_id', $tenant->id)
            ->leftJoin('tenant_customers', 'tenant_coverage_zones.id', '=', 'tenant_customers.zone_id')
            ->select('tenant_coverage_zones.name', DB::raw('count(tenant_customers.id) as customers_count'), DB::raw('sum(tenant_customers.monthly_bill) as total_mrr'))
            ->groupBy('tenant_coverage_zones.id', 'tenant_coverage_zones.name')
            ->orderByDesc('total_mrr')
            ->take(5)
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.reports.revenue', compact(
            'tenant',
            'preset',
            'startDate',
            'endDate',
            'stream',
            'zoneId',
            'packageId',
            'from',
            'to',
            'allZones',
            'allPackages',
            'totalRevenue',
            'periodRetailRevenue',
            'periodWholesaleRevenue',
            'periodRechargeRevenue',
            'periodGatewayRevenue',
            'periodDirectIncome',
            'periodCarrierCost',
            'periodExpenses',
            'netProfit',
            'profitMarginPct',
            'totalMrr',
            'arpu',
            'growthRatePct',
            'activeSubscribersCount',
            'totalSubscribersCount',
            'newSubscribersCount',
            'churnedSubscribersCount',
            'netSubscriberGrowth',
            'totalOutstandingDue',
            'monthlyMatrix',
            'topPackages',
            'paymentChannels',
            'topZones',
            'currencySymbol'
        ));
    }

    /**
     * Standalone Full-Page Print Statement View
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $preset = $request->get('preset', 'last_12_months');
        $startDate = $request->get('start_date') ?: $request->get('date_from');
        $endDate = $request->get('end_date') ?: $request->get('date_to');

        [$from, $to] = $this->resolveDateRanges($preset, $startDate, $endDate);

        $periodRetailRevenue = (float) TenantCustomerPayment::where('tenant_id', $tenant->id)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');
        $periodWholesaleRevenue = (float) TenantResellerInvoice::where('tenant_id', $tenant->id)
            ->whereIn('payment_status', ['PAID', 'PARTIAL'])
            ->whereBetween('paid_at', [$from, $to])
            ->sum('paid_amount');
        $periodDirectIncome = (float) TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'INCOME')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $totalRevenue = $periodRetailRevenue + $periodWholesaleRevenue + $periodDirectIncome;
        $periodExpenses = (float) TenantExpenseTransaction::where('tenant_id', $tenant->id)
            ->where('type', 'EXPENSE')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $netProfit = $totalRevenue - $periodExpenses;
        $monthlyMatrix = $this->build12MonthMatrix($tenant->id);
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.reports.revenue_print', compact(
            'tenant',
            'preset',
            'from',
            'to',
            'totalRevenue',
            'periodRetailRevenue',
            'periodWholesaleRevenue',
            'periodDirectIncome',
            'periodExpenses',
            'netProfit',
            'monthlyMatrix',
            'currencySymbol'
        ));
    }

    /**
     * Streamed CSV / Excel Export of Historical Growth Analytics
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $matrix = $this->build12MonthMatrix($tenant->id);
        $filename = 'isp_revenue_growth_analytics_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($matrix, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Title block
            fputcsv($handle, [$tenant->name . ' - Monthly Revenue & Business Growth Analytics']);
            fputcsv($handle, ['Generated At', date('d M Y, h:i A')]);
            fputcsv($handle, []);

            // Header row
            fputcsv($handle, [
                'Month',
                'Retail Revenue',
                'Wholesale Revenue',
                'Direct Incomes',
                'Total Revenue',
                'Operating OPEX',
                'Net Profit',
                'Profit Margin %',
                'MoM Growth %',
                'Active Subscribers',
                'ARPU',
            ]);

            foreach ($matrix as $row) {
                fputcsv($handle, [
                    $row['month_label'],
                    $row['retail_revenue'],
                    $row['wholesale_revenue'],
                    $row['direct_revenue'],
                    $row['total_revenue'],
                    $row['total_expenses'],
                    $row['net_profit'],
                    $row['profit_margin_pct'] . '%',
                    $row['growth_rate_pct'] . '%',
                    $row['active_subscribers'],
                    $row['arpu'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Build 12-Month Continuous Growth & Comparative Matrix
     */
    protected function build12MonthMatrix(int $tenantId): array
    {
        $matrix = [];
        $now = Carbon::now();

        for ($i = 11; $i >= 0; $i--) {
            $targetMonth = (clone $now)->subMonths($i);
            $monthStart = (clone $targetMonth)->startOfMonth();
            $monthEnd = (clone $targetMonth)->endOfMonth();
            $monthKey = $targetMonth->format('Y-m');
            $monthLabel = $targetMonth->format('M Y');

            // Retail Revenue in Month
            $retail = (float) TenantCustomerPayment::where('tenant_id', $tenantId)
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            // Wholesale Revenue in Month
            $wholesale = (float) TenantResellerInvoice::where('tenant_id', $tenantId)
                ->whereIn('payment_status', ['PAID', 'PARTIAL'])
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('paid_amount');

            // Direct Revenue in Month
            $direct = (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
                ->where('type', 'INCOME')
                ->where('status', 'APPROVED')
                ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $totalRev = $retail + $wholesale + $direct;

            // Operating OPEX in Month
            $expenses = (float) TenantExpenseTransaction::where('tenant_id', $tenantId)
                ->where('type', 'EXPENSE')
                ->where('status', 'APPROVED')
                ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

            $netProf = $totalRev - $expenses;
            $marginPct = $totalRev > 0 ? round(($netProf / $totalRev) * 100, 1) : 0;

            // Active subscriber base in month
            $activeSubs = TenantCustomer::where('tenant_id', $tenantId)
                ->where('created_at', '<=', $monthEnd)
                ->where(function($q) use ($monthEnd) {
                    $q->where('status', '!=', 'disconnected')
                      ->orWhere('updated_at', '>', $monthEnd);
                })
                ->count();
            if ($activeSubs === 0) {
                $activeSubs = TenantCustomer::where('tenant_id', $tenantId)->count();
            }

            $arpuVal = $activeSubs > 0 ? round($retail / $activeSubs, 2) : 0;

            $matrix[$monthKey] = [
                'month_key' => $monthKey,
                'month_label' => $monthLabel,
                'retail_revenue' => $retail,
                'wholesale_revenue' => $wholesale,
                'direct_revenue' => $direct,
                'total_revenue' => $totalRev,
                'total_expenses' => $expenses,
                'net_profit' => $netProf,
                'profit_margin_pct' => $marginPct,
                'growth_rate_pct' => 0.0, // calculated next
                'active_subscribers' => $activeSubs,
                'arpu' => $arpuVal,
            ];
        }

        // Compute MoM Growth %
        $prevRev = 0;
        foreach ($matrix as $key => &$data) {
            if ($prevRev > 0) {
                $data['growth_rate_pct'] = round((($data['total_revenue'] - $prevRev) / $prevRev) * 100, 1);
            } else {
                $data['growth_rate_pct'] = 0.0;
            }
            $prevRev = $data['total_revenue'];
        }

        return array_reverse(array_values($matrix));
    }

    /**
     * Resolve Period Range & Previous Comparison Range
     */
    protected function resolveDateRanges(string $preset, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                $from = (clone $now)->startOfDay();
                $to = (clone $now)->endOfDay();
                $prevFrom = (clone $now)->subDay()->startOfDay();
                $prevTo = (clone $now)->subDay()->endOfDay();
                break;

            case 'yesterday':
                $from = (clone $now)->subDay()->startOfDay();
                $to = (clone $now)->subDay()->endOfDay();
                $prevFrom = (clone $now)->subDays(2)->startOfDay();
                $prevTo = (clone $now)->subDays(2)->endOfDay();
                break;

            case 'this_week':
                $from = (clone $now)->startOfWeek()->startOfDay();
                $to = (clone $now)->endOfWeek()->endOfDay();
                $prevFrom = (clone $from)->subWeek()->startOfWeek()->startOfDay();
                $prevTo = (clone $from)->subWeek()->endOfWeek()->endOfDay();
                break;

            case 'last_month':
                $from = (clone $now)->subMonth()->startOfMonth();
                $to = (clone $now)->subMonth()->endOfMonth();
                $prevFrom = (clone $now)->subMonths(2)->startOfMonth();
                $prevTo = (clone $now)->subMonths(2)->endOfMonth();
                break;

            case 'this_quarter':
                $from = (clone $now)->startOfQuarter();
                $to = (clone $now)->endOfQuarter();
                $prevFrom = (clone $from)->subQuarter()->startOfQuarter();
                $prevTo = (clone $from)->subQuarter()->endOfQuarter();
                break;

            case 'this_year':
                $from = (clone $now)->startOfYear();
                $to = (clone $now)->endOfYear();
                $prevFrom = (clone $now)->subYear()->startOfYear();
                $prevTo = (clone $now)->subYear()->endOfYear();
                break;

            case 'last_12_months':
                $from = (clone $now)->subMonths(11)->startOfMonth();
                $to = (clone $now)->endOfMonth();
                $prevFrom = (clone $from)->subMonths(12)->startOfMonth();
                $prevTo = (clone $from)->subDay()->endOfDay();
                break;

            case 'all':
                $from = Carbon::create(2020, 1, 1)->startOfDay();
                $to = (clone $now)->addYear()->endOfDay();
                $prevFrom = Carbon::create(2019, 1, 1)->startOfDay();
                $prevTo = Carbon::create(2019, 12, 31)->endOfDay();
                break;

            case 'custom':
                if ($customStart && $customEnd) {
                    $from = Carbon::parse($customStart)->startOfDay();
                    $to = Carbon::parse($customEnd)->endOfDay();
                    $daysDiff = max(1, $from->diffInDays($to));
                    $prevFrom = (clone $from)->subDays($daysDiff)->startOfDay();
                    $prevTo = (clone $from)->subSecond();
                } else {
                    $from = (clone $now)->startOfMonth();
                    $to = (clone $now)->endOfMonth();
                    $prevFrom = (clone $now)->subMonth()->startOfMonth();
                    $prevTo = (clone $now)->subMonth()->endOfMonth();
                }
                break;

            case 'this_month':
            default:
                $from = (clone $now)->startOfMonth();
                $to = (clone $now)->endOfMonth();
                $prevFrom = (clone $now)->subMonth()->startOfMonth();
                $prevTo = (clone $now)->subMonth()->endOfMonth();
                break;
        }

        return [$from, $to, $prevFrom, $prevTo];
    }
}
