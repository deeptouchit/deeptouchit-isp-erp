<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    /**
     * Display RBAC roles and permissions management directory.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Role::with(['permissions:id,name', 'users:id,first_name,last_name,email,username'])
            ->withCount(['permissions', 'users']);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Type Filter (all, system, custom)
        if ($type = $request->input('type')) {
            if ($type === 'system') {
                $query->where('is_system', true);
            } elseif ($type === 'custom') {
                $query->where('is_system', false);
            }
        }

        $roles = $query->orderBy('is_system', 'desc')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allRoles = Role::all();
        $totalRoles = $allRoles->count();
        $systemRoles = $allRoles->where('is_system', true)->count();
        $totalPermissions = Permission::count();
        $assignedUsers = DB::table('model_has_roles')->distinct('model_id')->count();

        $stats = [
            'total_roles' => $totalRoles,
            'system_roles' => $systemRoles,
            'total_permissions' => $totalPermissions,
            'assigned_users' => $assignedUsers,
        ];

        // All available permissions grouped by category prefix
        $allPermissions = Permission::orderBy('name')->get()->map(function ($p) {
            $parts = explode('.', $p->name);
            $category = ucfirst($parts[0] ?? 'General');
            if ($category === 'Dns') $category = 'DNS & Domains';
            if ($category === 'Servers') $category = 'Infrastructure & Servers';
            if ($category === 'Email') $category = 'Mail & Postfix';
            if ($category === 'Security') $category = 'Security & Firewall';
            if ($category === 'Billing') $category = 'Billing & Payments';
            if ($category === 'Support') $category = 'Support & Helpdesk';
            if ($category === 'Cron') $category = 'Automation & Tasks';
            if ($category === 'Api') $category = 'REST API & Webhooks';
            if ($category === 'Admin') $category = 'Administration & Users';

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $category,
            ];
        });

        return Inertia::render('Admin/Administration/Roles/Index', [
            'roles' => $roles,
            'stats' => $stats,
            'allPermissions' => $allPermissions,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Store a newly created custom role.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:60', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:30'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $roleSlug = $validated['name'] ? Str::slug($validated['name'], '_') : Str::slug($validated['display_name'], '_');

        $role = Role::create([
            'name' => $roleSlug,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'is_system' => false,
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'role_created',
            'description' => "Created RBAC Role '{$role->display_name}' ({$role->name}) with " . count($validated['permissions'] ?? []) . " permissions.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'role' => $role->name,
                'display_name' => $role->display_name,
                'permissions_count' => count($validated['permissions'] ?? []),
            ],
        ]);

        return redirect()->route('admin.administration.roles')
            ->with('success', "Role '{$role->display_name}' created successfully.");
    }

    /**
     * Update existing role and sync its permissions.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:30'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldValues = $role->only(['display_name', 'description', 'color']);
        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
        ]);

        // Don't strip permissions from super_admin root
        if ($role->name !== 'super_admin' && isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'role_updated',
            'description' => "Updated RBAC Role '{$role->display_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $role->only(['display_name', 'description', 'color']),
        ]);

        return redirect()->route('admin.administration.roles')
            ->with('success', "Role '{$role->display_name}' updated successfully.");
    }

    /**
     * Delete custom role.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('create', User::class);

        if ($role->is_system || $role->name === 'super_admin') {
            return redirect()->route('admin.administration.roles')
                ->with('error', 'System root roles cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('admin.administration.roles')
                ->with('error', "Cannot delete role '{$role->display_name}' because it is assigned to {$role->users()->count()} active user(s).");
        }

        $name = $role->display_name ?: $role->name;
        $role->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'role_deleted',
            'description' => "Deleted RBAC Role '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.administration.roles')
            ->with('success', "Role '{$name}' deleted successfully.");
    }
}
