<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminApiUserController extends Controller
{
    /**
     * Display API users and service account identities.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ApiUser::with('user:id,first_name,last_name,email,role,username');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        // Role Filter
        if ($role = $request->input('role')) {
            if ($role !== 'all') {
                $query->where('role', $role);
            }
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $apiUsers = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $all = ApiUser::all();
        $activeUsers = $all->where('status', 'active')->count();
        $totalCalls = $all->sum('total_calls');
        $ipLockedUsers = $all->filter(fn($u) => !empty($u->ip_restrictions) && count($u->ip_restrictions) > 0)->count();
        $suspendedUsers = $all->where('status', 'suspended')->count();

        $stats = [
            'total_users' => $all->count(),
            'active_users' => $activeUsers,
            'total_calls' => $totalCalls,
            'ip_locked_users' => $ipLockedUsers,
            'suspended_users' => $suspendedUsers,
        ];

        return Inertia::render('Admin/Api/Users/Index', [
            'apiUsers' => $apiUsers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Store a newly created API user / service account.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:api_users,email'],
            'role' => ['required', 'in:service_account,admin_bot,reseller_api,read_only,custom'],
            'scopes' => ['nullable', 'array'],
            'ip_restrictions' => ['nullable', 'array'],
            'rate_limit_multiplier' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $apiUser = ApiUser::create([
            'user_id' => auth()->id() ?: 1,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'scopes' => $validated['scopes'] ?? ['servers:read'],
            'ip_restrictions' => $validated['ip_restrictions'] ?? [],
            'rate_limit_multiplier' => $validated['rate_limit_multiplier'] ?? 1,
            'status' => 'active',
            'total_calls' => 0,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_user_created',
            'description' => "Created Service Account '{$apiUser->name}' ({$apiUser->email}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [
                'name' => $apiUser->name,
                'email' => $apiUser->email,
                'role' => $apiUser->role,
            ],
        ]);

        return redirect()->route('admin.api.users')
            ->with('success', "Service Account '{$apiUser->name}' created successfully.");
    }

    /**
     * Update existing API user settings.
     */
    public function update(Request $request, ApiUser $apiUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('api_users', 'email')->ignore($apiUser->id)],
            'role' => ['required', 'in:service_account,admin_bot,reseller_api,read_only,custom'],
            'scopes' => ['nullable', 'array'],
            'ip_restrictions' => ['nullable', 'array'],
            'rate_limit_multiplier' => ['nullable', 'integer', 'min:1', 'max:10'],
            'status' => ['required', 'in:active,suspended,revoked'],
        ]);

        $oldValues = $apiUser->only(['name', 'email', 'role', 'scopes', 'ip_restrictions', 'rate_limit_multiplier', 'status']);
        $apiUser->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_user_updated',
            'description' => "Updated Service Account '{$apiUser->name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $apiUser->only(['name', 'email', 'role', 'scopes', 'ip_restrictions', 'rate_limit_multiplier', 'status']),
        ]);

        return redirect()->route('admin.api.users')
            ->with('success', "Service Account '{$apiUser->name}' updated successfully.");
    }

    /**
     * Toggle status between active and suspended.
     */
    public function toggleStatus(Request $request, ApiUser $apiUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $newStatus = $apiUser->status === 'active' ? 'suspended' : 'active';
        $apiUser->update(['status' => $newStatus]);

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_user_status_toggled',
            'description' => "Changed status of Service Account '{$apiUser->name}' to {$newStatus}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['status' => $apiUser->getOriginal('status')],
            'new_values' => ['status' => $newStatus],
        ]);

        return redirect()->route('admin.api.users')
            ->with('success', "Service Account status changed to {$newStatus}.");
    }

    /**
     * Delete API user permanently.
     */
    public function destroy(Request $request, ApiUser $apiUser): RedirectResponse
    {
        $this->authorize('create', User::class);

        $name = $apiUser->name;
        $apiUser->delete();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_user_deleted',
            'description' => "Permanently deleted Service Account '{$name}'.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['name' => $name],
            'new_values' => [],
        ]);

        return redirect()->route('admin.api.users')
            ->with('success', "Service Account '{$name}' deleted permanently.");
    }
}
