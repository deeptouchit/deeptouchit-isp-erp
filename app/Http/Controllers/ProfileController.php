<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $userId = $user->id;

        // Account Stats
        $stats = [
            'active_subscriptions' => \App\Models\Subscription::where('user_id', $userId)->where('status', 'active')->count(),
            'active_websites' => \App\Models\Website::whereHas('subscription', fn($q) => $q->where('user_id', $userId))->count(),
            'unpaid_invoices' => \App\Models\Invoice::where('user_id', $userId)->where('status', 'sent')->count(),
            'open_tickets' => \App\Models\SupportTicket::where('user_id', $userId)->whereIn('status', ['open', 'waiting'])->count(),
            'credit_balance' => (float) ($user->credit_balance ?? 0.00),
        ];

        // Security logs
        $activityLogs = \App\Models\ActivityLog::where('user_id', $userId)
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at ? $log->created_at->diffForHumans() : 'Just now',
                    'formatted_date' => $log->created_at ? $log->created_at->format('M d, Y H:i:s') : 'N/A',
                ];
            });

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'profile' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'company' => $user->company,
                'address' => $user->address,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country ?: 'Bangladesh',
                'zip_code' => $user->zip_code,
                'role' => $user->role,
                'status' => $user->status,
                'credit_balance' => (float) ($user->credit_balance ?? 0.00),
                'email_verified' => (bool) $user->email_verified_at,
                'two_factor_enabled' => (bool) $user->two_factor_confirmed_at,
                'last_login_at' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Recently',
                'last_login_ip' => $user->last_login_ip ?: '127.0.0.1',
                'member_since' => $user->created_at ? $user->created_at->format('F d, Y') : '2026',
            ],
            'stats' => $stats,
            'activityLogs' => $activityLogs,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => 'User updated personal contact profile and billing information.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return Redirect::route('profile.edit')->with('status', 'Profile contact information updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->forceDelete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
