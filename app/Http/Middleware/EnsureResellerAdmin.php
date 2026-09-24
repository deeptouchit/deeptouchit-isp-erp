<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResellerAdmin
{
    /**
     * Handle an incoming request for Reseller Admin Only routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('tenant.login');
        }

        $user = Auth::user();

        // Check if user has Reseller Admin privileges
        if ($user->isOwner() || $user->isIspAdmin() || $user->isResellerAdmin()) {
            return $next($request);
        }

        // Collectors or Techs attempting to access admin-only finance / staff routes
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'এই অ্যাকশনটিতে প্রবেশের জন্য রিসেলার অ্যাডমিন পারমিশন প্রয়োজন।',
            ], 403);
        }

        return redirect()->route('reseller.dashboard')->with('warning', 'আপনার এই মডিউলে প্রবেশের অনুমতি নেই। এটি শুধুমাত্র রিসেলার অ্যাডমিনের জন্য সংরক্ষিত।');
    }
}
