<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Platform Owner accessing tenant route
        if ($user->isOwner()) {
            if (!$user->tenant_id) {
                $defaultTenant = \App\Models\Tenant::first();
                if ($defaultTenant) {
                    $user->tenant_id = $defaultTenant->id;
                    $user->save();
                }
            }
            return $next($request);
        }

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

        // Ensure user belongs to a Tenant
        if (!$user->tenant_id) {
            $defaultTenant = \App\Models\Tenant::first();
            if ($defaultTenant) {
                $user->tenant_id = $defaultTenant->id;
                $user->save();
            } else {
                Auth::logout();
                return redirect()->route('tenant.login')->withErrors([
                    'email' => 'Your account is not associated with an ISP Tenant.',
                ]);
            }
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            $defaultTenant = \App\Models\Tenant::first();
            if ($defaultTenant) {
                $user->tenant_id = $defaultTenant->id;
                $user->save();
                $tenant = $defaultTenant;
            } else {
                Auth::logout();
                return redirect()->route('tenant.login')->withErrors([
                    'email' => 'ISP Tenant company record not found.',
                ]);
            }
        }

        // If Tenant is suspended, only allow access to billing settlement routes and logout
        if ($tenant->isSuspended()) {
            if (!$request->routeIs('tenant.billing.*') && !$request->routeIs('tenant.logout')) {
                return redirect()->route('tenant.billing.suspended');
            }
        }

        return $next($request);
    }
}
