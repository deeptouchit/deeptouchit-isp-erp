<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\TenantAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerUserController extends Controller
{
    protected TenantAuditService $auditService;

    public function __construct(TenantAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of all system users across all tenants with KPI metrics and filtering.
     */
    public function index(Request $request)
    {
        // 1. KPI Summary Cards (Strictly 6 Cards)
        $totalUsers = User::count();
        $superAdmins = User::whereIn('role', ['owner', 'super_admin'])->count();
        $ispAdmins = User::where('role', 'isp_admin')->count();
        $ispStaff = User::whereIn('role', ['isp_manager', 'isp_technician', 'isp_collector', 'technician', 'collector'])->count();
        $resellers = User::where('role', 'like', 'reseller_%')->orWhereNotNull('reseller_id')->count();
        $activeUsers = User::where('status', 'active')->count();

        // 2. Query Builder with Multi-Filters
        $query = User::with(['tenant', 'reseller']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tenant_id')) {
            if ($request->tenant_id === 'none') {
                $query->whereNull('tenant_id');
            } else {
                $query->where('tenant_id', $request->tenant_id);
            }
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = in_array((int)$request->get('per_page'), [10, 20, 50, 100]) ? (int)$request->get('per_page') : 20;

        $users = $query->latest('id')->paginate($perPage)->withQueryString();

        // Reference Data for Dropdowns and Modals
        $tenants = Tenant::orderBy('name')->get();
        $rolesList = [
            'owner' => 'Platform Owner (Super Admin)',
            'isp_admin' => 'ISP Super Admin',
            'isp_manager' => 'ISP Manager',
            'isp_technician' => 'NOC / Field Technician',
            'isp_collector' => 'Bill Collector',
            'reseller_admin' => 'Sub-ISP / Reseller Admin',
            'reseller_manager' => 'Sub-ISP Manager',
            'reseller_technician' => 'Sub-ISP Technician',
            'reseller_collector' => 'Sub-ISP Collector',
        ];
        $ownerCapabilities = config('owner_capabilities', []);

        return view('owner.users.index', compact(
            'users',
            'totalUsers',
            'superAdmins',
            'ispAdmins',
            'ispStaff',
            'resellers',
            'activeUsers',
            'tenants',
            'rolesList',
            'ownerCapabilities'
        ));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $this->auditService->log(
            $user->tenant_id,
            'user_created',
            "Created new system user '{$user->name}' ({$user->email}) with role '{$user->role}'.",
            ['user_id' => $user->id, 'role' => $user->role, 'tenant_id' => $user->tenant_id]
        );

        return redirect()->route('owner.users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'role' => ['required', Rule::in($this->allowedRoles())],
            'status' => ['required', 'in:active,inactive,suspended'],
            'password' => ['nullable', 'string', 'min:6'],
            'owner_capabilities' => ['nullable', 'array'],
            'owner_capabilities.*' => ['string', Rule::in(config('owner_capabilities', []))],
        ]);

        if ($user->id === Auth::id() && ($validated['status'] !== 'active' || $validated['role'] !== $user->role)) {
            return back()->with('error', 'You cannot disable or demote your own owner account.');
        }
        if ($this->isProtectedOwner($user) && $validated['role'] !== $user->role && $this->activeOwnerCount() <= 1) {
            return back()->with('error', 'The last active super-admin cannot be demoted.');
        }
        if ($this->isProtectedOwner($user) && $validated['status'] !== 'active' && $this->activeOwnerCount() <= 1) {
            return back()->with('error', 'The last active super-admin cannot be disabled.');
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['owner_capabilities'] = $user->isOwner()
            ? array_values(array_unique($validated['owner_capabilities'] ?? []))
            : null;
        $user->update($validated);

        $this->auditService->log(
            $user->tenant_id,
            'user_updated',
            "Updated user profile for '{$user->name}' ({$user->email}).",
            ['user_id' => $user->id, 'role' => $user->role, 'status' => $user->status]
        );

        return redirect()->route('owner.users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Reset the password for a specific user.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->auditService->log(
            $user->tenant_id,
            'password_reset',
            "Platform Owner reset password for user '{$user->name}' ({$user->email}).",
            ['user_id' => $user->id]
        );

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'message' => "Password for '{$user->name}' has been reset successfully.",
            ]);
        }

        return back()->with('success', "Password for user '{$user->name}' has been reset successfully.");
    }

    /**
     * Toggle the active/inactive status of a user.
     */
    public function toggleStatus(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            $msg = 'You cannot disable your own active super admin account.';
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if ($user->status === 'active' && $this->isProtectedOwner($user) && $this->activeOwnerCount() <= 1) {
            return back()->with('error', 'The last active super-admin cannot be disabled.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $this->auditService->log(
            $user->tenant_id,
            'user_status_toggled',
            "Changed status of user '{$user->name}' ({$user->email}) to '{$newStatus}'.",
            ['user_id' => $user->id, 'status' => $newStatus]
        );

        $stateText = $newStatus === 'active' ? 'activated' : 'deactivated';

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "User '{$user->name}' has been {$stateText}.",
            ]);
        }

        return back()->with('success', "User '{$user->name}' has been {$stateText} successfully.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own active super admin account.');
        }

        if ($this->isProtectedOwner($user) && $this->activeOwnerCount() <= 1) {
            return back()->with('error', 'The last active super-admin cannot be deleted.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $tenantId = $user->tenant_id;

        $this->auditService->log(
            $tenantId,
            'user_deleted',
            "Deleted user '{$userName}' ({$userEmail}).",
            ['user_id' => $user->id]
        );

        $user->delete();

        return redirect()->route('owner.users.index')->with('success', "User '{$userName}' ({$userEmail}) was removed successfully.");
    }

    private function allowedRoles(): array
    {
        return [
            'owner', 'super_admin', 'isp_admin', 'isp_manager', 'isp_technician',
            'isp_collector', 'technician', 'collector', 'reseller_admin',
            'reseller_manager', 'reseller_technician', 'reseller_collector',
        ];
    }

    private function isProtectedOwner(User $user): bool
    {
        return in_array($user->role, ['owner', 'super_admin'], true);
    }

    private function activeOwnerCount(): int
    {
        return User::whereIn('role', ['owner', 'super_admin'])
            ->where('status', 'active')
            ->count();
    }

    /**
     * Impersonate / Direct login as any user.
     */
    public function impersonate(User $user)
    {
        if (session()->has('impersonator_id')) {
            return back()->with('error', 'End the current impersonation session before starting another one.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('info', 'You are already logged in as this user.');
        }

        $this->auditService->log(
            $user->tenant_id,
            'user_impersonated',
            "Super Admin impersonated user '{$user->name}' ({$user->email}, role: {$user->role}).",
            ['target_user_id' => $user->id, 'target_role' => $user->role]
        );

        session()->regenerate();

        session([
            'impersonator_id' => Auth::id(),
            'impersonation_initiator_id' => Auth::id(),
            'impersonated_user_id' => $user->id,
            'impersonated_user_name' => $user->name,
            'impersonated_tenant_id' => $user->tenant_id,
            'impersonated_target_id' => $user->id,
            'impersonated_target_type' => 'user',
            'impersonation_type' => 'user',
            'impersonation_started_at' => now()->toIso8601String(),
            'impersonation_ip' => request()->ip(),
            'impersonation_user_agent' => request()->userAgent(),
        ]);

        Auth::login($user);

        if ($user->isOwner()) {
            return redirect()->route('owner.dashboard')->with('success', "Logged in as {$user->name}.");
        }

        $roleTitle = $user->role_badge['label'] ?? $user->role;
        return redirect()->route('tenant.dashboard')->with('success', "Logged in as {$user->name} ({$roleTitle}).");
    }
}
