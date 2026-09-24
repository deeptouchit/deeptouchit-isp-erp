<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionController extends Controller
{
    /**
     * Display RBAC permissions matrix and capability catalog.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = Permission::with('roles:id,name,display_name,color')->withCount('roles');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($category = $request->input('category')) {
            if ($category !== 'all') {
                $query->where('category', $category);
            }
        }

        $permissions = $query->orderBy('category')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allPermissions = Permission::all();
        $totalPermissions = $allPermissions->count();
        $functionalModules = $allPermissions->pluck('category')->unique()->count();
        $activeRoles = Role::count();
        $accessBindings = DB::table('role_has_permissions')->count();

        $stats = [
            'total_permissions' => $totalPermissions,
            'functional_modules' => $functionalModules,
            'active_roles' => $activeRoles,
            'access_bindings' => $accessBindings,
        ];

        $roles = Role::select('id', 'name', 'display_name', 'color', 'is_system')->orderBy('id')->get();
        $categories = Permission::distinct('category')->pluck('category')->filter()->values();

        return Inertia::render('Admin/Administration/Permissions/Index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Store a newly created custom permission.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:permissions,name'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create([
            'name' => Str::slug($validated['name'], '.'),
            'guard_name' => 'web',
        ]);
        $permission->category = $validated['category'];
        $permission->description = $validated['description'] ?? null;
        $permission->save();

        // Auto assign to super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permission);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'permission_created',
            'description' => "Created Permission capability '{$permission->name}' under category '{$permission->category}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'name' => $permission->name,
                'category' => $permission->category,
            ],
        ]);

        return redirect()->route('admin.administration.permissions')
            ->with('success', "Permission '{$permission->name}' created successfully.");
    }

    /**
     * Toggle a single permission assignment for a role in the matrix.
     */
    public function toggleRole(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'permission_id' => ['required', 'exists:permissions,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permission = Permission::findOrFail($validated['permission_id']);

        if ($role->name === 'super_admin') {
            return redirect()->route('admin.administration.permissions')
                ->with('error', 'Super Administrator role inherently maintains all capabilities.');
        }

        if ($role->hasPermissionTo($permission->name)) {
            $role->revokePermissionTo($permission->name);
            $actionText = 'revoked from';
        } else {
            $role->givePermissionTo($permission->name);
            $actionText = 'granted to';
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'role_permission_toggled',
            'description' => "Permission '{$permission->name}' {$actionText} role '{$role->display_name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'role' => $role->name,
                'permission' => $permission->name,
            ],
        ]);

        return redirect()->route('admin.administration.permissions')
            ->with('success', "Permission '{$permission->name}' {$actionText} {$role->display_name}.");
    }

    /**
     * Delete custom permission.
     */
    public function destroy(Request $request, Permission $permission): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = $permission->name;
        $permission->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'permission_deleted',
            'description' => "Deleted Permission '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.administration.permissions')
            ->with('success', "Permission '{$name}' deleted successfully.");
    }
}
