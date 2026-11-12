<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantUserController extends Controller
{
    /**
     * Resolve active tenant safely.
     */
    protected function getTenant(): Tenant
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = Tenant::first();
        }
        if (!$tenant) {
            abort(404, __('ISP Tenant record not found.'));
        }
        return $tenant;
    }

    /**
     * Display a listing of system user accounts & logins for the active tenant.
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $search = trim($request->input('search', ''));
        $roleFilter = $request->input('role', 'all');
        $statusFilter = $request->input('status', 'all');
        $scopeFilter = $request->input('scope', 'all'); // 'all', 'isp', 'reseller'
        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Active more filters counter for 2-Tier Filter System
        $activeMoreCount = 0;
        if ($scopeFilter !== 'all' && !empty($scopeFilter)) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;

        // Base Query
        $query = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'owner')
            ->with(['reseller']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== 'all' && !empty($roleFilter)) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter !== 'all' && !empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if ($scopeFilter === 'isp') {
            $query->whereNull('reseller_id');
        } elseif ($scopeFilter === 'reseller') {
            $query->whereNotNull('reseller_id');
        }

        $users = $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();

        // 6 Summary Metric Cards (AGENTS.md Rule 2.B)
        $allTenantUsers = User::where('tenant_id', $tenantId)->where('role', '!=', 'owner')->get();
        $totalUsers = $allTenantUsers->count();
        $adminUsers = $allTenantUsers->where('role', 'isp_admin')->count();
        $staffUsers = $totalUsers - $adminUsers;
        $activeUsers = $allTenantUsers->where('status', 'active')->count();
        $suspendedUsers = $allTenantUsers->whereIn('status', ['suspended', 'inactive', 'disabled'])->count();
        $assignedRolesCount = $allTenantUsers->pluck('role')->unique()->filter()->count();

        $stats = [
            'total_users' => $totalUsers,
            'admin_users' => $adminUsers,
            'staff_users' => $staffUsers,
            'active_users' => $activeUsers,
            'suspended_users' => $suspendedUsers,
            'assigned_roles' => $assignedRolesCount,
        ];

        // Available active roles for dropdowns
        $availableRoles = TenantRole::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('is_system', 'desc')
            ->orderBy('display_name', 'asc')
            ->get();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.users.index', compact(
            'tenant',
            'currencySymbol',
            'users',
            'stats',
            'availableRoles',
            'search',
            'roleFilter',
            'statusFilter',
            'scopeFilter',
            'perPage',
            'activeMoreCount',
            'isMoreOpen'
        ));
    }

    /**
     * Store a newly created user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'phone' => 'nullable|string|max:30',
            'role' => 'required|string|max:50',
            'password' => 'required|string|min:6|max:100',
            'status' => 'required|in:active,suspended,inactive',
            'designation' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'designation' => $request->designation,
            'staff_id' => User::generateNextStaffId($tenant->id),
        ]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'USER_ACCOUNT_CREATED',
            'description' => __("Created system user login account ':name' (:email) with role ':role'.", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __("User account ':name' created successfully.", ['name' => $user->name]));
    }

    /**
     * Fetch Single User account details for AJAX Modal / Edit
     */
    public function show($id): JsonResponse
    {
        $tenant = $this->getTenant();
        $user = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->with(['reseller'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'user' => $user,
            'role_badge' => $user->role_badge,
            'status_badge' => $user->status_badge,
            'scope_badge' => $user->scope_badge,
        ]);
    }

    /**
     * Update an existing user account.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:30',
            'role' => 'required|string|max:50',
            'password' => 'nullable|string|min:6|max:100',
            'status' => 'required|in:active,suspended,inactive',
            'designation' => 'nullable|string|max:100',
        ]);

        // Safety: Do not allow logged-in user to suspend their own account
        if (Auth::id() === $user->id && $request->status !== 'active') {
            return back()->with('error', __('You cannot suspend or deactivate your currently logged-in account.'));
        }

        $userData = [
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
            'designation' => $request->designation,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'USER_ACCOUNT_UPDATED',
            'description' => __("Updated system user login account ':name' (:email).", [
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __("User account ':name' updated successfully.", ['name' => $user->name]));
    }

    /**
     * Toggle User Account Status (Active / Suspended)
     */
    public function toggleStatus(Request $request, $id): RedirectResponse|JsonResponse
    {
        $tenant = $this->getTenant();
        $user = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->findOrFail($id);

        if (Auth::id() === $user->id) {
            $msg = __('You cannot suspend your own logged-in user account.');
            return $request->wantsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        $isActivated = $user->status === 'active';
        $activityDescription = $isActivated
            ? __("User account ':name' (:email) is now activated.", ['name' => $user->name, 'email' => $user->email])
            : __("User account ':name' (:email) is now suspended.", ['name' => $user->name, 'email' => $user->email]);
        $jsonMessage = $isActivated
            ? __("User account ':name' is now activated.", ['name' => $user->name])
            : __("User account ':name' is now suspended.", ['name' => $user->name]);
        $flashMessage = $isActivated
            ? __("User account ':name' has been activated successfully.", ['name' => $user->name])
            : __("User account ':name' has been suspended successfully.", ['name' => $user->name]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'USER_STATUS_TOGGLED',
            'description' => $activityDescription,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $user->status,
                'message' => $jsonMessage,
            ]);
        }

        return back()->with('success', $flashMessage);
    }

    /**
     * Reset User Password directly
     */
    public function resetPassword(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->findOrFail($id);

        $request->validate([
            'new_password' => 'required|string|min:6|max:100',
        ]);

        $user->password = Hash::make($request->new_password);
        $user->save();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'USER_PASSWORD_RESET',
            'description' => __("Reset password for user account ':name' (:email).", [
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __("Password for user ':name' has been updated successfully.", ['name' => $user->name]));
    }

    /**
     * Delete User Account
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $user = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->findOrFail($id);

        if (Auth::id() === $user->id) {
            return back()->with('error', __('You cannot delete your own logged-in user account.'));
        }

        if ($user->role === 'isp_admin') {
            $adminCount = User::where('tenant_id', $tenant->id)->where('role', 'isp_admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', __('Cannot delete the sole remaining Super Admin account for this tenant.'));
            }
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'USER_ACCOUNT_DELETED',
            'description' => __("Deleted user login account ':name' (:email).", [
                'name' => $userName,
                'email' => $userEmail,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __("User account ':name' deleted successfully.", ['name' => $userName]));
    }

    /**
     * Streamed CSV Export of User Accounts
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $filename = 'users_directory_' . date('Y_m_d_His') . '.csv';

        $users = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->with(['reseller'])
            ->orderBy('id', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($users, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Company Title
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, [__('SYSTEM USER ACCOUNTS & LOGIN DIRECTORY')]);
            fputcsv($handle, [__('Exported At'), now()->translatedFormat('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Column Headers
            fputcsv($handle, [
                __('SL'),
                __('Staff / User ID'),
                __('Full Name'),
                __('Email / Login ID'),
                __('Phone'),
                __('Assigned Role'),
                __('Designation'),
                __('Account Status'),
                __('Scope Affiliation'),
                __('Created At'),
            ]);

            foreach ($users as $index => $u) {
                fputcsv($handle, [
                    $index + 1,
                    $u->staff_id ?? ('USR-' . str_pad($u->id, 4, '0', STR_PAD_LEFT)),
                    $u->name,
                    $u->email,
                    $u->phone ?? $u->mobile ?? __('N/A'),
                    __($u->role_badge['label'] ?? $u->role),
                    $u->designation ?? __('N/A'),
                    __($u->status_badge['label'] ?? ucfirst($u->status ?? 'active')),
                    __($u->scope_badge['label'] ?? 'ISP Core'),
                    $u->created_at ? $u->created_at->translatedFormat('d-M-Y h:i A') : __('N/A'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Printable A4 Statement of User Directory
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $users = User::where('tenant_id', $tenant->id)
            ->where('role', '!=', 'owner')
            ->with(['reseller'])
            ->orderBy('id', 'desc')
            ->get();

        return view('tenant.users.print', compact('tenant', 'currencySymbol', 'users'));
    }
}

