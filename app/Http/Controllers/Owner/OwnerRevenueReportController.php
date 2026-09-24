<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantWalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerRevenueReportController extends Controller
{
    /**
     * Display the Owner Revenue & Income Statement Report.
     */
    public function index(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $tenantId = $request->get('tenant_id');
        $method = $request->get('payment_method');
        $status = $request->get('status', 'paid');

        // Parse Date Range
        [$from, $to] = $this->resolveDateRange($preset, $startDate, $endDate);

        // Base Query for Paid Invoices / Income
        $invoiceQuery = SaasInvoice::with(['tenant', 'plan']);

        if ($from && $to) {
            $invoiceQuery->whereBetween('created_at', [$from, $to]);
        }

        if ($tenantId) {
            $invoiceQuery->where('tenant_id', $tenantId);
        }

        if ($method) {
            $invoiceQuery->where('payment_method', $method);
        }

        if ($status !== 'all') {
            $invoiceQuery->where('status', $status);
        }

        // Summary Calculations for Selected Period
        $totalInvoicesCount = (clone $invoiceQuery)->count();
        $totalCollectedIncome = (clone $invoiceQuery)->where('status', 'paid')->sum('amount');
        if ($totalCollectedIncome == 0) {
            $totalCollectedIncome = (clone $invoiceQuery)->where('status', 'paid')->sum('paid_amount');
        }

        $totalDiscountGiven = (clone $invoiceQuery)->sum('discount');
        $totalOutstandingDue = (clone $invoiceQuery)->whereIn('status', ['unpaid', 'overdue', 'partially_paid'])->sum('amount');

        // Lifetime Metrics
        $lifetimeIncome = SaasInvoice::where('status', 'paid')->sum('amount');
        $lifetimeOutstanding = SaasInvoice::whereIn('status', ['unpaid', 'overdue'])->sum('amount');
        $currentMrr = Tenant::where('status', 'active')
            ->join('saas_plans', 'tenants.saas_plan_id', '=', 'saas_plans.id')
            ->sum('saas_plans.monthly_price');

        // Breakdown by Payment Methods
        $methodBreakdown = (clone $invoiceQuery)
            ->where('status', 'paid')
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Breakdown by SaaS Plans
        $planBreakdown = (clone $invoiceQuery)
            ->where('status', 'paid')
            ->select('saas_plan_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('saas_plan_id')
            ->with('plan')
            ->get();

        // Top Revenue Contributing Tenants
        $topTenants = (clone $invoiceQuery)
            ->where('status', 'paid')
            ->select('tenant_id', DB::raw('count(*) as invoices_count'), DB::raw('sum(amount) as total_paid'))
            ->groupBy('tenant_id')
            ->with('tenant')
            ->orderByDesc('total_paid')
            ->take(5)
            ->get();

        // Daily / Monthly Chart Trend (Past 30 Days or 12 Months)
        $chartLabels = [];
        $chartData = [];

        if ($preset === 'this_year' || $preset === 'all_time') {
            // Group by Month
            for ($i = 11; $i >= 0; $i--) {
                $m = now()->subMonths($i);
                $chartLabels[] = $m->format('M Y');
                $chartData[] = (float) SaasInvoice::where('status', 'paid')
                    ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                    ->sum('amount');
            }
        } else {
            // Group by Last 14 Days
            for ($i = 13; $i >= 0; $i--) {
                $d = now()->subDays($i);
                $chartLabels[] = $d->format('d M');
                $chartData[] = (float) SaasInvoice::where('status', 'paid')
                    ->whereDate('created_at', $d->toDateString())
                    ->sum('amount');
            }
        }

        // Paginated Transactions / Invoices List
        $invoices = $invoiceQuery->latest('created_at')->paginate(20)->withQueryString();
        $tenantsList = Tenant::select('id', 'name')->orderBy('name')->get();

        return view('owner.reports.revenue', compact(
            'invoices',
            'tenantsList',
            'preset',
            'from',
            'to',
            'tenantId',
            'method',
            'status',
            'totalInvoicesCount',
            'totalCollectedIncome',
            'totalDiscountGiven',
            'totalOutstandingDue',
            'lifetimeIncome',
            'lifetimeOutstanding',
            'currentMrr',
            'methodBreakdown',
            'planBreakdown',
            'topTenants',
            'chartLabels',
            'chartData'
        ));
    }

    /**
     * Export the filtered income report to CSV.
     */
    public function exportCsv(Request $request)
    {
        $preset = $request->get('date_preset', 'this_month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $tenantId = $request->get('tenant_id');
        $method = $request->get('payment_method');
        $status = $request->get('status', 'paid');

        [$from, $to] = $this->resolveDateRange($preset, $startDate, $endDate);

        $query = SaasInvoice::with(['tenant', 'plan']);

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($method) {
            $query->where('payment_method', $method);
        }
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->latest('created_at')->get();
        $filename = 'owner_income_report_' . now()->format('Y_m_d_His') . '.csv';

        return new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice #', 'Date', 'ISP Tenant', 'Plan', 'Payment Method', 'Trx ID', 'Gross Amount (BDT)', 'Discount', 'Status']);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->invoice_no ?? '#INV-' . $row->id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->tenant->name ?? 'N/A',
                    $row->plan->name ?? 'Standard',
                    ucfirst($row->payment_method ?? 'Manual'),
                    $row->trx_id ?? 'N/A',
                    number_format($row->amount, 2, '.', ''),
                    number_format($row->discount, 2, '.', ''),
                    ucfirst($row->status)
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to resolve start and end dates.
     */
    private function resolveDateRange(?string $preset, ?string $start, ?string $end): array
    {
        switch ($preset) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'yesterday':
                return [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            case 'all_time':
                return [null, null];
            case 'custom':
                if ($start && $end) {
                    return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
                }
                return [now()->startOfMonth(), now()->endOfMonth()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }
}
