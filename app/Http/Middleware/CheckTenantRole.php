<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantRole
{
    /**
     * Handle an incoming request to verify tenant user role.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('tenant.login');
        }

        $user = Auth::user();

        // Platform Owner bypass
        if ($user->isOwner()) {
            return $next($request);
        }

        foreach ($roles as $role) {
            $subRoles = explode(',', $role);
            foreach ($subRoles as $sr) {
                $trimmed = trim($sr);
                if ($trimmed && $user->role === $trimmed) {
                    return $next($request);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: Your assigned role is not permitted to access this module.',
            ], 403);
        }

        return redirect()->route('tenant.dashboard')->with('error', 'Access Denied: Your assigned role is not authorized for that section.');
    }
}
