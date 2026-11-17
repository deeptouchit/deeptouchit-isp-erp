<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OwnerAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isOwner()) {
            return redirect()->route('owner.dashboard');
        }
        return view('owner.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $key = 'owner-login:'.Str::lower(trim($credentials['email'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => 1])]);
        }
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            if (!$user->isOwner() || $user->status !== 'active') {
                RateLimiter::hit($key, 60);
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Access denied. You do not have SaaS Platform Owner permissions.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($key);
            $request->session()->regenerate();
            return redirect()->intended(route('owner.dashboard'))->with('success', 'Welcome! Successfully logged in to the SaaS Owner Console.');
        }

        RateLimiter::hit($key, 60);
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login')->with('success', 'You have been logged out successfully.');
    }
}
