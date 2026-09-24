<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountNotSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Admins are not restricted by client suspension
            if ($user->role === 'admin') {
                return $next($request);
            }

            if ($user->status === 'suspended') {
                // Allowed routes for suspended users: suspended page, logout, invoices, payments, support tickets
                $allowedRouteNames = [
                    'client.suspended',
                    'logout',
                    'billing.invoices',
                    'billing.invoice.show',
                    'billing.checkout',
                    'payment.pay',
                    'tickets.index',
                    'tickets.show',
                    'tickets.create',
                    'tickets.store',
                    'profile.edit',
                    'profile.update',
                ];

                $currentRouteName = $request->route()?->getName();

                if (in_array($currentRouteName, $allowedRouteNames)) {
                    return $next($request);
                }

                if ($request->is('logout') || $request->is('client/suspended*') || $request->is('client/billing/invoices*') || $request->is('client/tickets*')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your hosting account has been suspended. Please check outstanding invoices or contact support.',
                        'redirect_url' => route('client.suspended'),
                    ], 403);
                }

                return redirect()->route('client.suspended');
            }
        }

        return $next($request);
    }
}
