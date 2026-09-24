<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            if (!$user->isOwner()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Access denied. You do not have SaaS Platform Owner permissions.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('owner.dashboard'))->with('success', 'Welcome! Successfully logged in to the SaaS Owner Console.');
        }

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
