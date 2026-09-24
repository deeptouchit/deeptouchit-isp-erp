<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAdministratorController extends Controller
{
    /**
     * Display privileged platform administrators and root operators.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::where('role', 'admin');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('admin_role', 'like', "%{$search}%");
            });
        }

        // Role Title Filter
        if ($role = $request->input('role')) {
            if ($role !== 'all') {
                $query->where('admin_role', $role);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $administrators = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allAdmins = User::where('role', 'admin')->get();
        $totalAdmins = $allAdmins->count();
        $activeAdmins = $allAdmins->where('status', 'active')->count();
        $twoFaProtected = $allAdmins->filter(fn($u) => !empty($u->two_factor_confirmed_at) || $u->two_factor_enforced)->count();
        $ipRestricted = $allAdmins->filter(fn($u) => !empty($u->ip_allowlist) && count($u->ip_allowlist) > 0)->count();

        $stats = [
            'total_admins' => $totalAdmins,
            'active_admins' => $activeAdmins,
            'two_factor_protected' => $twoFaProtected,
            'ip_restricted' => $ipRestricted,
        ];

        return Inertia::render('Admin/Administration/Administrators/Index', [
            'administrators' => $administrators,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Store a newly created administrator.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admin_role' => ['required', 'string', 'max:60'],
            'ip_allowlist' => ['nullable', 'array'],
            'two_factor_enforced' => ['boolean'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $ipAllowlist = !empty($validated['ip_allowlist']) 
            ? array_values(array_filter(array_map('trim', $validated['ip_allowlist']))) 
            : null;

        $admin = User::create([
            'uuid' => (string) Str::uuid(),
            'role' => 'admin',
            'admin_role' => $validated['admin_role'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'ip_allowlist' => $ipAllowlist,
            'two_factor_enforced' => $validated['two_factor_enforced'] ?? false,
            'status' => $validated['status'],
            'email_verified_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'admin_user_created',
            'description' => "Created Administrator '{$admin->first_name} {$admin->last_name}' ({$admin->admin_role}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'email' => $admin->email,
                'admin_role' => $admin->admin_role,
            ],
        ]);

        return redirect()->route('admin.administration.administrators')
            ->with('success', "Administrator '{$admin->first_name} {$admin->last_name}' created successfully.");
    }

    /**
     * Update existing administrator.
     */
    public function update(Request $request, User $admin): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($admin->id)],
            'username' => ['required', 'string', 'max:60', Rule::unique('users')->ignore($admin->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'admin_role' => ['required', 'string', 'max:60'],
            'ip_allowlist' => ['nullable', 'array'],
            'two_factor_enforced' => ['boolean'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $ipAllowlist = !empty($validated['ip_allowlist']) 
            ? array_values(array_filter(array_map('trim', $validated['ip_allowlist']))) 
            : null;

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'phone' => $validated['phone'] ?? null,
            'admin_role' => $validated['admin_role'],
            'ip_allowlist' => $ipAllowlist,
            'two_factor_enforced' => $validated['two_factor_enforced'] ?? false,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $oldValues = $admin->only(['first_name', 'last_name', 'email', 'username', 'admin_role', 'status']);
        $admin->update($updateData);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'admin_user_updated',
            'description' => "Updated Administrator profile for '{$admin->first_name} {$admin->last_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $admin->only(['first_name', 'last_name', 'email', 'username', 'admin_role', 'status']),
        ]);

        return redirect()->route('admin.administration.administrators')
            ->with('success', "Administrator profile for '{$admin->first_name} {$admin->last_name}' updated.");
    }

    /**
     * Toggle status between active and suspended.
     */
    public function toggleStatus(Request $request, User $admin): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($admin->id === auth()->id() || $admin->id === 1) {
            return redirect()->route('admin.administration.administrators')
                ->with('error', 'You cannot suspend your own account or the primary root administrator.');
        }

        $newStatus = $admin->status === 'active' ? 'suspended' : 'active';
        $admin->update(['status' => $newStatus]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'admin_status_toggled',
            'description' => "Changed status of Administrator '{$admin->first_name} {$admin->last_name}' to {$newStatus}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $admin->getOriginal('status')],
            'new_values' => ['status' => $newStatus],
        ]);

        return redirect()->route('admin.administration.administrators')
            ->with('success', "Administrator status changed to {$newStatus}.");
    }

    /**
     * Reset 2FA configuration for an administrator.
     */
    public function reset2fa(Request $request, User $admin): RedirectResponse
    {
        $this->authorize('create', User::class);

        $admin->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'admin_2fa_reset',
            'description' => "Reset Two-Factor Authentication credentials for '{$admin->first_name} {$admin->last_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['two_factor_confirmed_at' => null],
        ]);

        return redirect()->route('admin.administration.administrators')
            ->with('success', "Two-Factor Authentication reset for '{$admin->first_name} {$admin->last_name}'.");
    }

    /**
     * Delete an administrator account.
     */
    public function destroy(Request $request, User $admin): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($admin->id === auth()->id() || $admin->id === 1) {
            return redirect()->route('admin.administration.administrators')
                ->with('error', 'You cannot delete your own account or the primary root administrator.');
        }

        $name = "{$admin->first_name} {$admin->last_name}";
        $admin->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'admin_deleted',
            'description' => "Deleted Administrator account '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.administration.administrators')
            ->with('success', "Administrator '{$name}' deleted successfully.");
    }
}
