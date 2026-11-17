<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Tenancy\TenantContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    /**
     * Handle an incoming request for ISP Tenant Portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('tenant.login');
        }

        $user = Auth::user();

        // Sub-ISP Reseller Users accessing ISP Tenant Admin portal should be routed to Reseller portal
        if ($user->isResellerUser()) {
            if ($user->isResellerCollector() && \Illuminate\Support\Facades\Route::has('reseller.collector.dashboard')) {
                return redirect()->route('reseller.collector.dashboard');
            }
            if ($user->isResellerTech() && \Illuminate\Support\Facades\Route::has('reseller.tech.dashboard')) {
                return redirect()->route('reseller.tech.dashboard');
            }
            return redirect()->route('reseller.dashboard');
        }

        // Never infer membership from the first company in the database.
        if ($user->status !== 'active' || !$user->tenant_id || !$user->tenant) {
            abort(403);
        }
        $tenant = $user->tenant;

        // If Tenant is suspended, only allow access to billing settlement routes and logout
        if ($tenant->isSuspended()) {
            if (!$request->routeIs('tenant.billing.*') && !$request->routeIs('tenant.logout')) {
                return redirect()->route('tenant.billing.suspended');
            }
        }

        $previousTenant = TenantContext::getTenant();
        TenantContext::setTenant($tenant);
        try {
            return $next($request);
        } finally {
            TenantContext::setTenant($previousTenant);
        }
    }
}
