<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantWallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OwnerDashboardController extends Controller
{
    public function index()
    {
        // 1. Tenant Overview Metrics
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $expiringSoonTenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(7)])
            ->count();

        // 2. Financial & Revenue Metrics
        $mrr = Tenant::where('status', 'active')
            ->join('saas_plans', 'tenants.saas_plan_id', '=', 'saas_plans.id')
            ->sum('saas_plans.monthly_price');

        $collectedThisMonth = SaasInvoice::where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('paid_amount');

        if ($collectedThisMonth == 0) {
            $collectedThisMonth = SaasInvoice::where('status', 'paid')
                ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
        }

        $totalRevenue = SaasInvoice::where('status', 'paid')->sum('paid_amount');
        if ($totalRevenue == 0) {
            $totalRevenue = SaasInvoice::where('status', 'paid')->sum('amount');
        }

        $pendingInvoicesCount = SaasInvoice::whereIn('status', ['unpaid', 'overdue', 'partially_paid'])->count();
        $pendingInvoicesAmount = SaasInvoice::whereIn('status', ['unpaid', 'overdue', 'partially_paid'])->sum('amount');

        // 3. Support Tickets Metrics
        $openTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress', 'answered'])->count();
        $urgentTicketsCount = SupportTicket::whereIn('status', ['open', 'in_progress'])->where('priority', 'urgent')->count();
        $recentTickets = SupportTicket::with(['tenant', 'user'])
            ->whereIn('status', ['open', 'in_progress', 'answered'])
            ->latest('last_reply_at')
            ->take(5)
            ->get();

        // 4. Wallet & SMS Metrics
        $totalWalletBalance = TenantWallet::sum('balance') ?? 0;
        $totalSmsSent = \App\Models\SmsLog::where('status', '!=', 'failed')->count();
        $smsBalanceInfo = Cache::remember('owner.dashboard.sms_balance', 120, fn () => \App\Services\SmsService::getBalance());

        // 5. Recent Tenants & Invoices
        $recentTenants = Tenant::with(['plan'])->latest()->take(6)->get();
        $recentInvoices = SaasInvoice::with(['tenant', 'plan'])->latest()->take(6)->get();
        $plans = SaasPlan::withCount('tenants')->get();
        $totalPlans = $plans->where('is_active', true)->count();

        // 6. Last 6 Months Chart Analytics Data
        $chartLabels = [];
        $chartRevenue = [];
        $chartTenants = [];

        $chartStart = now()->subMonths(5)->startOfMonth();
        $chartInvoices = SaasInvoice::where('status', 'paid')
            ->where('created_at', '>=', $chartStart)
            ->get(['created_at', 'amount']);
        $chartTenantRows = Tenant::where('created_at', '>=', $chartStart)
            ->get(['created_at']);
        $runningTenantCount = Tenant::where('created_at', '<', $chartStart)->count();

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            $chartLabels[] = $monthLabel;

            $rev = $chartInvoices
                ->filter(fn ($invoice) => $invoice->created_at->isSameMonth($month))
                ->sum('amount');
            $chartRevenue[] = (float)$rev;

            $runningTenantCount += $chartTenantRows
                ->filter(fn ($tenant) => $tenant->created_at->isSameMonth($month))
                ->count();
            $chartTenants[] = $runningTenantCount;
        }

        return view('owner.dashboard', compact(
            'totalTenants',
            'activeTenants',
            'suspendedTenants',
            'expiringSoonTenants',
            'mrr',
            'collectedThisMonth',
            'totalRevenue',
            'pendingInvoicesCount',
            'pendingInvoicesAmount',
            'openTicketsCount',
            'urgentTicketsCount',
            'recentTickets',
            'totalWalletBalance',
            'totalSmsSent',
            'smsBalanceInfo',
            'recentTenants',
            'recentInvoices',
            'plans',
            'totalPlans',
            'chartLabels',
            'chartRevenue',
            'chartTenants'
        ));
    }
}
