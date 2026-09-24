<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\Collector\TenantCollectorDashboardController;
use App\Models\Tenant;
use App\Models\TenantOlt;
use App\Models\TenantRouter;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantDashboardController extends Controller
{
    /**
     * Display the ISP Tenant Landing Dashboard (https://somitysoft.com/admin/dashboard).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Reseller Sub-ISP Partner Tier -> redirect to reseller dashboard
        if ($user && $user->isResellerUser()) {
            return redirect()->route('reseller.dashboard');
        }

        // 2. ISP Core Collector Tier -> Render Collector Hub directly at /admin/dashboard
        if ($user && ($user->isResellerCollector() || in_array($user->role, ['isp_collector', 'collector'], true))) {
            return app()->make(TenantCollectorDashboardController::class)->index($request);
        }

        $tenant = $user?->tenant ?? ($user?->tenant_id ? Tenant::find($user->tenant_id) : Tenant::first());

        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        $tenant->loadMissing(['plan', 'activeSubscription']);

        // Network hardware metrics
        $routerCount = TenantRouter::where('tenant_id', $tenant->id)->count();
        $onlineRouters = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $oltCount = TenantOlt::where('tenant_id', $tenant->id)->count();

        // Support tickets metrics
        $openTicketsCount = $tenant->tickets()->whereIn('status', ['open', 'in_progress', 'pending'])->count();

        // Recent Invoices
        $recentInvoices = $tenant->invoices()->latest()->limit(5)->get();

        // Subscription expiry calculation
        $daysRemaining = null;
        if ($tenant->subscription_expires_at) {
            $daysRemaining = (int) now()->diffInDays($tenant->subscription_expires_at, false);
        }

        return view('tenant.dashboard', compact(
            'tenant',
            'user',
            'routerCount',
            'onlineRouters',
            'oltCount',
            'openTicketsCount',
            'recentInvoices',
            'daysRemaining'
        ));
    }
}
