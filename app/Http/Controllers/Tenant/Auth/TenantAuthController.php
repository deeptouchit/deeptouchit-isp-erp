<?php

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    /**
     * Display the Single Unified Portal Login Form.
     */
    public function showLoginForm(Request $request, ?string $slug = null)
    {
        // If already authenticated, intelligently redirect to appropriate dashboard
        if (Auth::check()) {
            $user = Auth::user();
            return redirect($this->getDashboardRedirectForUser($user));
        }

        $tenant = null;
        if ($slug) {
            $tenant = Tenant::where('slug', strtolower($slug))->first();
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }

        return view('tenant.auth.login', compact('tenant'));
    }

    /**
     * Resolve the exact landing dashboard URL based on User Tier & Role.
     */
    public function getDashboardRedirectForUser($user): string
    {
        // 1. Platform Super Admin / Owner
        if ((method_exists($user, 'isOwner') && $user->isOwner()) || ($user->role ?? null) === 'owner') {
            return \Illuminate\Support\Facades\Route::has('owner.dashboard') ? route('owner.dashboard') : url('/owner/dashboard');
        }

        // 2. Reseller Sub-ISP Partner Tier
        if (!empty($user->reseller_id) || (method_exists($user, 'isResellerUser') && $user->isResellerUser())) {
            return \Illuminate\Support\Facades\Route::has('reseller.dashboard') ? route('reseller.dashboard') : url('/reseller/dashboard');
        }

        // 3. ISP Core HQ Tier (All ISP Roles: Admin, Manager, Collector, Technician) -> https://somitysoft.com/admin/dashboard
        return \Illuminate\Support\Facades\Route::has('tenant.dashboard') ? route('tenant.dashboard') : url('/admin/dashboard');
    }

    /**
     * Handle an incoming Single Unified Portal authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $loginInput = trim($credentials['email']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Multi-field credential checking (email, phone, mobile, staff_id, name)
        $authPassed = false;
        $loginField = 'email';

        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $loginField = 'email';
            $authPassed = Auth::attempt(['email' => $loginInput, 'password' => $password], $remember);
        } else {
            // Try Phone
            if (Auth::attempt(['phone' => $loginInput, 'password' => $password], $remember)) {
                $authPassed = true;
                $loginField = 'phone';
            }
            // Try Mobile
            elseif (Auth::attempt(['mobile' => $loginInput, 'password' => $password], $remember)) {
                $authPassed = true;
                $loginField = 'mobile';
            }
            // Try Name/Username
            elseif (Auth::attempt(['name' => $loginInput, 'password' => $password], $remember)) {
                $authPassed = true;
                $loginField = 'name';
            }
            // Try Staff ID
            elseif (Auth::attempt(['staff_id' => $loginInput, 'password' => $password], $remember)) {
                $authPassed = true;
                $loginField = 'staff_id';
            }
        }

        if (!$authPassed) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'email' => 'আপনার দেওয়া লগইন তথ্য সঠিক নয়। ইমেইল/মোবাইল এবং পাসওয়ার্ড যাচাই করে পুনরায় চেষ্টা করুন।',
            ]);
        }

        $user = Auth::user();

        // Check if user account itself is inactive
        if (isset($user->status) && $user->status === 'inactive') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'আপনার ইউজার একাউন্টটি সাময়িকভাবে নিষ্ক্রিয় রয়েছে। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন।',
            ]);
        }

        $tenant = $user->tenant ?? ($user->tenant_id ? Tenant::find($user->tenant_id) : null);

        // Check if ISP Tenant Company is suspended
        if ($tenant && method_exists($tenant, 'isSuspended') && $tenant->isSuspended()) {
            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();
            return redirect()->route('tenant.billing.suspended')->with('warning', 'আপনার আইএসপি কোম্পানির সাবস্ক্রিপশন সাময়িকভাবে স্থগিত রয়েছে। বিল পরিশোধ করুন।');
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Record successful login in tenant activity logs
        try {
            if ($tenant) {
                TenantActivityLog::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'event_type' => 'user_login',
                    'description' => "User '{$user->name}' ({$user->role}) logged in successfully via single portal.",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => ['login_input' => $loginInput, 'login_field' => $loginField, 'role' => $user->role],
                ]);
            }
        } catch (\Throwable $e) {
            // Non-blocking log
        }

        $targetUrl = $this->getDashboardRedirectForUser($user);
        return redirect()->intended($targetUrl)->with('success', "স্বাগতম, {$user->name}!");
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login')->with('success', 'আপনি সফলভাবে লগআউট হয়েছেন।');
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "অতিরিক্ত লগইন চেষ্টার কারণে সাময়িক ব্লক করা হয়েছে। অনুগ্রহ করে {$seconds} সেকেন্ড পর চেষ্টা করুন।",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());
    }
}
