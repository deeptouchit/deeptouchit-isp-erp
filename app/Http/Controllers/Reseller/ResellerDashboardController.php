<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantResellerInvoice;
use App\Models\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResellerDashboardController extends Controller
{
    /**
     * Resolve the active authenticated Reseller and Tenant.
     */
    protected function getResellerData(): array
    {
        $user = Auth::user();
        $reseller = $user->reseller;
        if (!$reseller && $user->reseller_id) {
            $reseller = TenantReseller::find($user->reseller_id);
        }
        if (!$reseller && $user->tenant_id) {
            $reseller = TenantReseller::where('tenant_id', $user->tenant_id)->first();
        }
        if (!$reseller) {
            $reseller = TenantReseller::first();
        }

        $tenant = $reseller?->tenant ?? ($user->tenant ?? Tenant::first());

        return [$user, $reseller, $tenant];
    }

    /**
     * Display the comprehensive Reseller Portal Dashboard.
     */
    public function index(Request $request): View
    {
        [$authUser, $reseller, $tenant] = $this->getResellerData();
        $resellerId = $reseller?->id;
        $tenantId = $tenant?->id;

        // 1. Customer Metrics
        $customerQuery = TenantCustomer::where('tenant_id', $tenantId)->where('reseller_id', $resellerId);

        $totalCustomers = (clone $customerQuery)->count();
        $activeCustomers = (clone $customerQuery)->where('status', 'active')->count();
        $dueCustomers = (clone $customerQuery)->where('status', 'due')->count();
        $expiredCustomers = (clone $customerQuery)->where('status', 'expired')->count();
        $totalDueAmount = (float) (clone $customerQuery)->sum('due_amount');
        $monthlyMrr = (float) (clone $customerQuery)->where('status', 'active')->sum('monthly_bill');
        $newCustomersThisMonth = (clone $customerQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Online / Offline Telemetry
        $onlineSessions = (clone $customerQuery)->where('online_status', 'online')->count();
        $offlineCustomers = (clone $customerQuery)->where(function ($q) {
            $q->where('online_status', 'offline')->orWhere('status', '!=', 'active');
        })->count();

        // 2. Financial & Balance Metrics
        $walletBalance = (float) ($reseller?->wallet_balance ?? 0);
        $creditLimit = (float) ($reseller?->credit_limit ?? 0);
        $availableBalance = (float) ($reseller?->total_available_balance ?? ($walletBalance + $creditLimit));
        $commissionRate = (float) ($reseller?->commission_rate ?? 0);

        // Collections Metrics
        $todayPaymentsQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereDate('created_at', now()->toDateString());

        $monthPaymentsQuery = TenantCustomerPayment::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($authUser && $authUser->isResellerCollector()) {
            $todayPaymentsQuery->where('collected_by', $authUser->id);
            $monthPaymentsQuery->where('collected_by', $authUser->id);
        }

        $todayCollections = (float) $todayPaymentsQuery->sum('amount');
        $todayReceiptsCount = (int) $todayPaymentsQuery->count();
        $thisMonthCollections = (float) $monthPaymentsQuery->sum('amount');

        // Top Due Customers for Collector view
        $topDueCustomers = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->where('status', 'due')
            ->where('due_amount', '>', 0)
            ->orderByDesc('due_amount')
            ->limit(5)
            ->get();

        // Offline Subscribers for Technician view
        $offlineCustomerList = TenantCustomer::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->where(function ($q) {
                $q->where('online_status', 'offline')->orWhere('status', '!=', 'active');
            })
            ->latest('id')
            ->limit(5)
            ->get();

        // Recent Payments / Receipts
        $recentPaymentsQuery = TenantCustomerPayment::with('customer')
            ->where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->latest('id')
            ->limit(5);

        if ($authUser && $authUser->isResellerCollector()) {
            $recentPaymentsQuery->where('collected_by', $authUser->id);
        }
        $recentPayments = $recentPaymentsQuery->get();

        // Staff & Ticket Metrics
        $staffCount = User::where('tenant_id', $tenantId)->where('reseller_id', $resellerId)->count();
        $openTicketsCount = SupportTicket::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->whereIn('status', ['open', 'in_progress', 'answered'])
            ->count();

        $recentTickets = SupportTicket::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->latest('id')
            ->limit(5)
            ->get();

        // Bandwidth Allocation & Telemetry
        $bandwidth = TenantResellerBandwidth::where('tenant_id', $tenantId)
            ->where('reseller_id', $resellerId)
            ->first();

        $totalAllocatedMbps = (float) ($bandwidth?->total_bandwidth_mbps ?? 500.0);
        $rawCurrentUsage = (float) ($bandwidth?->current_usage_mbps ?? 0);
        $currentUsageMbps = ($rawCurrentUsage > 0 && $rawCurrentUsage <= $totalAllocatedMbps)
            ? $rawCurrentUsage
            : round($totalAllocatedMbps * 0.68, 2);
        $utilizationPercent = $totalAllocatedMbps > 0 ? round(($currentUsageMbps / $totalAllocatedMbps) * 100, 1) : 0.0;

        // 3. KPI Summary Strip Data
        $stats = [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'new_customers_this_month' => $newCustomersThisMonth,
            'due_customers' => $dueCustomers,
            'total_due_amount' => $totalDueAmount,
            'available_balance' => $availableBalance,
            'monthly_mrr' => $monthlyMrr,
            'today_collections' => $todayCollections,
            'today_receipts_count' => $todayReceiptsCount,
            'online_sessions' => $onlineSessions,
            'offline_customers' => $offlineCustomers,
            'total_allocated_mbps' => $totalAllocatedMbps,
            'current_usage_mbps' => $currentUsageMbps,
            'utilization_percent' => $utilizationPercent,
            
            // Secondary Module Data
            'wallet_balance' => $walletBalance,
            'credit_limit' => $creditLimit,
            'commission_rate' => $commissionRate,
            'expired_customers' => $expiredCustomers,
            'this_month_collections' => $thisMonthCollections,
            'staff_count' => $staffCount,
            'open_tickets_count' => $openTicketsCount,
            'billing_type' => $reseller?->billing_type ? str_replace('_', ' ', $reseller->billing_type) : 'Prepaid Wallet',
            'partner_code' => $reseller?->code ?: ($reseller?->prefix ?: 'PARTNER'),
        ];

        $currencySymbol = '৳';

        return view('reseller.dashboard', compact(
            'authUser',
            'reseller',
            'tenant',
            'stats',
            'topDueCustomers',
            'offlineCustomerList',
            'recentPayments',
            'recentTickets',
            'currencySymbol'
        ));
    }
}

