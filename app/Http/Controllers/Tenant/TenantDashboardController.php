<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Collector\TenantCollectorDashboardController;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantFieldJob;
use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Models\TenantRouter;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TenantDashboardController extends Controller
{
    /**
     * Display the ISP Tenant Landing Dashboard (https://somitysoft.com/admin/dashboard).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Reseller Sub-ISP Partner Tier -> redirect to reseller dashboard
        if ($user && method_exists($user, 'isResellerUser') && $user->isResellerUser()) {
            return redirect()->route('reseller.dashboard');
        }

        // 2. ISP Core Collector Tier -> Render Collector Hub directly at /admin/dashboard
        if ($user && ((method_exists($user, 'isResellerCollector') && $user->isResellerCollector()) || in_array($user->role ?? '', ['isp_collector', 'collector'], true))) {
            return app()->make(TenantCollectorDashboardController::class)->index($request);
        }

        $tenant = $user?->tenant ?? ($user?->tenant_id ? Tenant::find($user->tenant_id) : Tenant::first());

        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        if (method_exists($tenant, 'loadMissing')) {
            $tenant->loadMissing(['plan', 'activeSubscription']);
        }

        // Customer Metrics
        $totalCustomers = 0;
        $activeCustomers = 0;
        $expiredCustomers = 0;
        $dueCustomers = 0;
        $recentCustomers = collect();

        if (class_exists(TenantCustomer::class) && Schema::hasTable('tenant_customers')) {
            $customerQuery = TenantCustomer::where('tenant_id', $tenant->id);
            $totalCustomers = (clone $customerQuery)->count();
            $activeCustomers = (clone $customerQuery)->where('status', 'active')->count();
            $expiredCustomers = (clone $customerQuery)->whereIn('status', ['expired', 'suspended', 'inactive'])->count();
            $dueCustomers = (clone $customerQuery)->where('due_amount', '>', 0)->count();
            $recentCustomers = (clone $customerQuery)->latest()->limit(5)->get();
        }

        // Financial Metrics
        $todayCollection = 0;
        $monthlyRevenue = 0;
        $totalOutstandingDue = 0;
        $recentPayments = collect();

        if (class_exists(TenantCustomerPayment::class) && Schema::hasTable('tenant_customer_payments')) {
            $todayCollection = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->whereDate('created_at', today())
                ->sum('amount') ?? 0;

            $monthlyRevenue = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount') ?? 0;

            $recentPayments = TenantCustomerPayment::where('tenant_id', $tenant->id)
                ->with('customer')
                ->latest()
                ->limit(5)
                ->get();
        }

        if (class_exists(TenantCustomer::class) && Schema::hasTable('tenant_customers')) {
            $totalOutstandingDue = TenantCustomer::where('tenant_id', $tenant->id)->sum('due_amount') ?? 0;
        }

        // Network hardware metrics
        $routerCount = 0;
        $onlineRouters = 0;
        $oltCount = 0;
        $onuCount = 0;

        if (class_exists(TenantRouter::class) && Schema::hasTable('tenant_routers')) {
            $routerCount = TenantRouter::where('tenant_id', $tenant->id)->count();
            $onlineRouters = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        }
        if (class_exists(TenantOlt::class) && Schema::hasTable('tenant_olts')) {
            $oltCount = TenantOlt::where('tenant_id', $tenant->id)->count();
        }
        if (class_exists(TenantOnu::class) && Schema::hasTable('tenant_onus')) {
            $onuCount = TenantOnu::where('tenant_id', $tenant->id)->count();
        }

        // Support tickets & Field jobs
        $openTicketsCount = 0;
        if (method_exists($tenant, 'tickets')) {
            $openTicketsCount = $tenant->tickets()->whereIn('status', ['open', 'in_progress', 'pending'])->count();
        }
        $pendingFieldJobsCount = 0;
        if (class_exists(TenantFieldJob::class) && Schema::hasTable('tenant_field_jobs')) {
            $pendingFieldJobsCount = TenantFieldJob::where('tenant_id', $tenant->id)->whereIn('status', ['pending', 'assigned'])->count();
        }

        // Recent SaaS Invoices
        $recentInvoices = collect();
        if (method_exists($tenant, 'invoices')) {
            $recentInvoices = $tenant->invoices()->latest()->limit(5)->get();
        }

        // Subscription expiry calculation
        $daysRemaining = null;
        if ($tenant->subscription_expires_at) {
            $daysRemaining = (int) now()->diffInDays($tenant->subscription_expires_at, false);
        }

        return view('tenant.dashboard', compact(
            'tenant',
            'user',
            'totalCustomers',
            'activeCustomers',
            'expiredCustomers',
            'dueCustomers',
            'todayCollection',
            'monthlyRevenue',
            'totalOutstandingDue',
            'routerCount',
            'onlineRouters',
            'oltCount',
            'onuCount',
            'openTicketsCount',
            'pendingFieldJobsCount',
            'recentCustomers',
            'recentPayments',
            'recentInvoices',
            'daysRemaining'
        ));
    }
}
