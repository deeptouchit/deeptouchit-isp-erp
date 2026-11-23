<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerAccount
{
    public function handle(Request $request, Closure $next)
    {
        $account = Auth::guard('customer')->user();
        $tenant = $request->route('tenant');

        if (is_string($tenant)) {
            $tenant = Tenant::where('slug', $tenant)->firstOrFail();
        }

        if (!$account || !$account->isActive()) {
            return redirect()->route('customer.portal.login', ['tenant' => $tenant?->slug]);
        }

        abort_unless(!$tenant || (int) $account->tenant_id === (int) $tenant->id, 403);

        return $next($request);
    }
}
