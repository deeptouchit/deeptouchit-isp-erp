<?php

namespace App\Http\Controllers\CustomerPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerPortalAuthController extends Controller
{
    public function showLogin(Tenant $tenant)
    {
        return view('customer-portal.auth.login', compact('tenant'));
    }

    public function login(Request $request, Tenant $tenant)
    {
        $credentials = $request->validate([
            'login_identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $guard = Auth::guard('customer');
        $authenticated = $guard->attempt([
            'tenant_id' => $tenant->id,
            'login_identifier' => trim($credentials['login_identifier']),
            'status' => 'active',
            'password' => $credentials['password'],
        ], $request->boolean('remember'));
        $account = $guard->user();

        if (!$authenticated || !$account || !$account->isActive()) {
            $guard->logout();
            throw ValidationException::withMessages([
                'login_identifier' => 'The login details are invalid for this ISP.',
            ]);
        }

        $request->session()->regenerate();
        $account->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('customer.portal.clean.dashboard', ['tenant' => $tenant->slug]);
    }

    public function logout(Request $request, Tenant $tenant)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.portal.clean.login', ['tenant' => $tenant->slug]);
    }
}
