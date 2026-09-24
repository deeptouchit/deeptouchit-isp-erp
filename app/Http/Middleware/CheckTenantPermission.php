<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantPermission
{
    /**
     * Handle an incoming request to verify tenant user permission.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('tenant.login');
        }

        $user = Auth::user();

        // Universal access for Platform Owner and ISP Super Admin
        if ($user->isOwner() || $user->isIspAdmin()) {
            return $next($request);
        }

        // Check if user has ANY of the specified permissions
        foreach ($permissions as $permission) {
            // Support comma-separated permissions in single argument e.g. "customers.view,customers.edit"
            $subPerms = explode(',', $permission);
            foreach ($subPerms as $sp) {
                $trimmed = trim($sp);
                if ($trimmed && $user->hasPermission($trimmed)) {
                    return $next($request);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: You do not possess the required permissions to perform this action.',
            ], 403);
        }

        return redirect()->route('tenant.dashboard')->with('error', 'Access Denied: You do not have permission to access that section.');
    }
}
