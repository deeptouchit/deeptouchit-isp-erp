<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
{
    /**
     * Handle an incoming request and check tenant subscription status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce for ISP tenant users (exclude platform owner)
        if ($user && $user->role !== 'owner' && $user->tenant_id) {
            $tenant = $user->tenant;
            if ($tenant) {
                \App\Services\Tenancy\TenantContext::setTenant($tenant);
            }

            if ($tenant && $tenant->isSuspended()) {
                // Allow access to billing portal, payments, and logout
                $allowedRoutes = [
                    'tenant.billing*',
                    'tenant.payment*',
                    'tenant.invoices*',
                    'logout',
                ];

                $currentRoute = $request->route() ? $request->route()->getName() : '';

                $isAllowed = false;
                foreach ($allowedRoutes as $pattern) {
                    if ($currentRoute && \Illuminate\Support\Str::is($pattern, $currentRoute)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed && !$request->is('admin/billing*', 'admin/payment*', 'logout')) {
                    return redirect()->route('tenant.billing.suspended');
                }
            }
        }

        return $next($request);
    }
}
