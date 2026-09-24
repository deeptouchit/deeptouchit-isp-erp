<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTwoFactorController extends Controller
{
    /**
     * Display Two-Factor Authentication enforcement console and compliance roster.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::where('role', 'admin')->with('department:id,name');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('admin_role', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        // Compliance Status Filter
        if ($compliance = $request->input('compliance')) {
            if ($compliance === 'confirmed') {
                $query->whereNotNull('two_factor_confirmed_at');
            } elseif ($compliance === 'enforced_pending') {
                $query->where('two_factor_enforced', true)->whereNull('two_factor_confirmed_at');
            } elseif ($compliance === 'disabled') {
                $query->where('two_factor_enforced', false)->whereNull('two_factor_confirmed_at');
            }
        }

        $operators = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allOperators = User::where('role', 'admin')->get();
        $totalCount = $allOperators->count();
        $confirmedCount = $allOperators->whereNotNull('two_factor_confirmed_at')->count();
        $pendingCount = $allOperators->where('two_factor_enforced', true)->whereNull('two_factor_confirmed_at')->count();
        $disabledCount = $allOperators->where('two_factor_enforced', false)->whereNull('two_factor_confirmed_at')->count();

        $compliancePercentage = $totalCount > 0 ? (int) round(($confirmedCount / $totalCount) * 100) : 100;

        $stats = [
            'compliance_percentage' => $compliancePercentage,
            'confirmed_count' => $confirmedCount,
            'pending_count' => $pendingCount,
            'disabled_count' => $disabledCount,
            'total_operators' => $totalCount,
        ];

        $policy = [
            'admin_enforcement_policy' => SystemSetting::get('two_factor.admin_enforcement_policy', 'enforced_all'),
            'grace_period_days' => (int) SystemSetting::get('two_factor.grace_period_days', 3),
            'allowed_methods' => SystemSetting::get('two_factor.allowed_methods', ['totp_authenticator', 'email_otp', 'webauthn_hardware']),
            'remember_device_days' => (int) SystemSetting::get('two_factor.remember_device_days', 30),
            'client_policy' => SystemSetting::get('two_factor.client_policy', 'optional'),
        ];

        return Inertia::render('Admin/Administration/TwoFactor/Index', [
            'operators' => $operators,
            'stats' => $stats,
            'policy' => $policy,
            'filters' => $request->only(['search', 'compliance']),
        ]);
    }

    /**
     * Update global platform-wide 2FA IAM policy settings.
     */
    public function updatePolicy(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'admin_enforcement_policy' => ['required', 'in:enforced_all,optional,enforced_privileged'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:30'],
            'allowed_methods' => ['required', 'array', 'min:1'],
            'remember_device_days' => ['required', 'integer', 'min:0', 'max:90'],
            'client_policy' => ['required', 'in:optional,enforced_all'],
        ]);

        SystemSetting::set('two_factor.admin_enforcement_policy', $validated['admin_enforcement_policy'], 'security');
        SystemSetting::set('two_factor.grace_period_days', (string) $validated['grace_period_days'], 'security');
        SystemSetting::set('two_factor.allowed_methods', $validated['allowed_methods'], 'security');
        SystemSetting::set('two_factor.remember_device_days', (string) $validated['remember_device_days'], 'security');
        SystemSetting::set('two_factor.client_policy', $validated['client_policy'], 'security');

        if ($validated['admin_enforcement_policy'] === 'enforced_all') {
            User::where('role', 'admin')->update(['two_factor_enforced' => true]);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => '2fa_policy_updated',
            'description' => "Updated Platform-wide 2FA IAM Policy (Mode: {$validated['admin_enforcement_policy']}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => $validated,
        ]);

        return redirect()->route('admin.administration.2fa')
            ->with('success', '2FA Enforcement Policy updated successfully.');
    }

    /**
     * Enforce or relax 2FA requirement for an individual operator.
     */
    public function toggleEnforce(Request $request, User $user): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newEnforced = !$user->two_factor_enforced;
        $user->update(['two_factor_enforced' => $newEnforced]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'user_2fa_enforcement_toggled',
            'description' => ($newEnforced ? 'Enforced' : 'Relaxed') . " 2FA requirement on '{$user->first_name} {$user->last_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['two_factor_enforced' => !$newEnforced],
            'new_values' => ['two_factor_enforced' => $newEnforced],
        ]);

        return redirect()->route('admin.administration.2fa')
            ->with('success', "2FA requirement " . ($newEnforced ? 'enforced on' : 'relaxed for') . " {$user->first_name}.");
    }

    /**
     * Reset 2FA secret and recovery codes for an operator.
     */
    public function resetUser(Request $request, User $user): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'user_2fa_credentials_reset',
            'description' => "Reset 2FA security credentials for '{$user->first_name} {$user->last_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['two_factor_confirmed_at' => null],
        ]);

        return redirect()->route('admin.administration.2fa')
            ->with('success', "2FA security credentials reset for {$user->first_name}.");
    }
}
