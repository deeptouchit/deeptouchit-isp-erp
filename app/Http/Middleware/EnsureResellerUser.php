<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureResellerUser
{
    /**
     * Handle an incoming request for Reseller Partner Portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('tenant.login');
        }

        $user = Auth::user();

        if ($user->status !== 'active' || (!$user->isOwner() && (!$user->tenant_id || !$user->tenant))) {
            abort(403);
        }

        // Platform Owner or ISP Admin accessing reseller route (Impersonation / Overseeing)
        if ($user->isOwner() || ($user->isIspAdmin() && !$user->reseller_id)) {
            return $next($request);
        }

        // Ensure user belongs to a Reseller
        if (!$user->reseller_id && !$user->isResellerUser()) {
            Auth::logout();
            return redirect()->route('tenant.login')->withErrors([
                'email' => 'আপনার অ্যাকাউন্টটি কোনো অনুমোদিত রিসেলার প্যানেলের সাথে যুক্ত নয়।',
            ]);
        }

        // Ensure user account is active
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('tenant.login')->withErrors([
                'email' => 'আপনার ইউজার অ্যাকাউন্টটি নিষ্ক্রিয় করা আছে।',
            ]);
        }

        $reseller = $user->reseller;
        if (!$reseller || !$user->tenant_id || (int) $reseller->tenant_id !== (int) $user->tenant_id) {
            Auth::logout();
            return redirect()->route('tenant.login')->withErrors([
                'email' => 'রিসেলার পার্টনার প্রোফাইল রেকর্ড পাওয়া যায়নি।',
            ]);
        }

        if ($reseller->status === 'suspended' || $reseller->status === 'disabled' || $reseller->status === 'inactive') {
            Auth::logout();
            return redirect()->route('tenant.login')->withErrors([
                'email' => "রিসেলার পার্টনার অ্যাকাউন্টটি ({$reseller->name}) স্থগিত বা নিষ্ক্রিয় রয়েছে।",
            ]);
        }

        return $next($request);
    }
}
