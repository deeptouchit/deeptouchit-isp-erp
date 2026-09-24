<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\TenantActivityLog;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantRbacController extends Controller
{
    /**
     * Resolve active tenant safely.
     */
    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = \App\Models\Tenant::first();
        }
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Display Role-Based Access Control (RBAC) Dashboard & Matrix
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        // Auto-seed default standard ISP roles if none exist for this tenant
        $this->ensureDefaultRolesExist($tenantId);

        $search = $request->input('search');
        $typeFilter = $request->input('type'); // 'all', 'system', 'custom'
        $statusFilter = $request->input('status'); // 'all', 'active', 'disabled'
        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = TenantRole::where('tenant_id', $tenantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($typeFilter === 'system') {
            $query->where('is_system', true);
        } elseif ($typeFilter === 'custom') {
            $query->where('is_system', false);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $roles = $query->orderBy('is_system', 'desc')->orderBy('id', 'asc')->paginate($perPage)->withQueryString();

        // 6 Summary Metric Cards (AGENTS.md Rule 2.B)
        $allRoles = TenantRole::where('tenant_id', $tenantId)->get();
        $totalRoles = $allRoles->count();
        $systemRolesCount = $allRoles->where('is_system', true)->count();
        $customRolesCount = $allRoles->where('is_system', false)->count();
        
        $assignedStaffCount = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'owner')
            ->count();

        $permissionCatalog = TenantRole::getPermissionCatalog();
        $totalPermissions = 0;
        foreach ($permissionCatalog as $group) {
            $totalPermissions += count($group['permissions']);
        }

        $activeRolesCount = $allRoles->where('status', 'active')->count();

        $stats = [
            'total_roles' => $totalRoles,
            'system_roles' => $systemRolesCount,
            'custom_roles' => $customRolesCount,
            'assigned_staff' => $assignedStaffCount,
            'total_permissions' => $totalPermissions,
            'active_roles' => $activeRolesCount,
        ];

        return view('tenant.settings.rbac', compact(
            'tenant',
            'roles',
            'permissionCatalog',
            'stats',
            'search',
            'typeFilter',
            'statusFilter',
            'perPage'
        ));
    }

    /**
     * Store a newly created custom role
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->getTenant();

        $request->validate([
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|in:purple,blue,indigo,emerald,amber,cyan,rose,sky,slate',
            'icon' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'status' => 'required|in:active,disabled',
        ]);

        $baseSlug = Str::slug($request->display_name, '_');
        $slug = $baseSlug;
        $counter = 1;
        while (TenantRole::where('tenant_id', $tenant->id)->where('name', $slug)->exists()) {
            $slug = "{$baseSlug}_{$counter}";
            $counter++;
        }

        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => $slug,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon ?: 'fa-shield-halved',
            'is_system' => false,
            'permissions' => $request->permissions ?? [],
            'status' => $request->status,
        ]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? 'Admin',
            'event_type' => 'RBAC_ROLE_CREATED',
            'description' => "Created custom RBAC role '{$role->display_name}' ({$role->name}) with " . count($role->permissions ?? []) . " permissions.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Role '{$role->display_name}' created successfully.");
    }

    /**
     * Fetch Single Role details for AJAX Modal / Edit
     */
    public function show($id): JsonResponse
    {
        $tenant = $this->getTenant();
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        $assignedStaff = User::where('tenant_id', $tenant->id)
            ->where('role', $role->name)
            ->select('id', 'name', 'email', 'phone', 'mobile', 'staff_id', 'designation', 'status')
            ->get();

        return response()->json([
            'success' => true,
            'role' => $role,
            'assigned_staff' => $assignedStaff,
            'badge' => $role->badge,
        ]);
    }

    /**
     * Update an existing role and its permissions
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        $request->validate([
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|in:purple,blue,indigo,emerald,amber,cyan,rose,sky,slate',
            'icon' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'status' => 'required|in:active,disabled',
        ]);

        $role->update([
            'display_name' => $request->display_name,
            'description' => $request->description,
            'color' => $request->color,
            'icon' => $request->icon ?: $role->icon,
            'permissions' => $request->permissions ?? [],
            'status' => $request->status,
        ]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? 'Admin',
            'event_type' => 'RBAC_ROLE_UPDATED',
            'description' => "Updated RBAC role '{$role->display_name}' ({$role->name}) permissions matrix (" . count($role->permissions ?? []) . " capabilities).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Role '{$role->display_name}' updated successfully.");
    }

    /**
     * Clone an existing role into a new custom role
     */
    public function cloneRole(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $sourceRole = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        $newName = $sourceRole->display_name . ' (Copy)';
        $baseSlug = Str::slug($sourceRole->name . '_copy', '_');
        $slug = $baseSlug;
        $counter = 1;
        while (TenantRole::where('tenant_id', $tenant->id)->where('name', $slug)->exists()) {
            $slug = "{$baseSlug}_{$counter}";
            $counter++;
        }

        $cloned = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => $slug,
            'display_name' => $newName,
            'description' => "Cloned from '{$sourceRole->display_name}'. " . ($sourceRole->description ?? ''),
            'color' => $sourceRole->color,
            'icon' => $sourceRole->icon,
            'is_system' => false,
            'permissions' => $sourceRole->permissions ?? [],
            'status' => 'active',
        ]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? 'Admin',
            'event_type' => 'RBAC_ROLE_CLONED',
            'description' => "Cloned RBAC role '{$sourceRole->display_name}' into '{$cloned->display_name}'.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Role '{$sourceRole->display_name}' successfully duplicated as '{$cloned->display_name}'.");
    }

    /**
     * Toggle Role Active Status (AGENTS.md Section 5 Standard)
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($role->is_system && $role->name === 'isp_admin') {
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The ISP Super Admin system role cannot be disabled.',
                ], 422);
            }
            return back()->with('error', 'The ISP Super Admin system role cannot be disabled.');
        }

        $role->status = $role->status === 'active' ? 'disabled' : 'active';
        $role->save();

        $stateText = $role->status === 'active' ? 'enabled' : 'disabled';

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? 'Admin',
            'event_type' => 'RBAC_ROLE_STATUS_TOGGLED',
            'description' => "Role '{$role->display_name}' is now {$stateText}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'status' => $role->status,
                'message' => "Role '{$role->display_name}' is now {$stateText}.",
            ]);
        }

        return back()->with('success', "Role '{$role->display_name}' has been {$stateText} successfully.");
    }

    /**
     * Delete Custom Role
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($role->is_system) {
            return back()->with('error', "System core role '{$role->display_name}' is protected and cannot be deleted.");
        }

        $staffCount = User::where('tenant_id', $tenant->id)->where('role', $role->name)->count();
        if ($staffCount > 0) {
            return back()->with('error', "Cannot delete role '{$role->display_name}' because {$staffCount} active employees are currently assigned to it. Please reassign them first.");
        }

        $roleName = $role->display_name;
        $role->delete();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? 'Admin',
            'event_type' => 'RBAC_ROLE_DELETED',
            'description' => "Deleted custom RBAC role '{$roleName}'.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Custom role '{$roleName}' deleted successfully.");
    }

    /**
     * Dedicated A4 Printable Role-Based Access Control Matrix Statement
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $roles = TenantRole::where('tenant_id', $tenant->id)->orderBy('is_system', 'desc')->get();
        $permissionCatalog = TenantRole::getPermissionCatalog();

        $stats = [
            'total_roles' => $roles->count(),
            'system_roles' => $roles->where('is_system', true)->count(),
            'custom_roles' => $roles->where('is_system', false)->count(),
            'assigned_staff' => User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->count(),
        ];

        return view('tenant.settings.rbac_print', compact('tenant', 'roles', 'permissionCatalog', 'stats'));
    }

    /**
     * Streamed CSV Export of Roles & Permission Matrix
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $filename = 'isp_rbac_roles_matrix_' . date('Y_m_d_His') . '.csv';

        $roles = TenantRole::where('tenant_id', $tenant->id)->orderBy('is_system', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($roles, $tenant) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Header Block
            fputcsv($handle, [$tenant->company_name ?? $tenant->name]);
            fputcsv($handle, ['ROLE-BASED ACCESS CONTROL (RBAC) & PERMISSIONS AUDIT MATRIX']);
            fputcsv($handle, ['Generated At', date('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Columns
            fputcsv($handle, [
                'SL',
                'Role Display Name',
                'System Key / Slug',
                'Classification',
                'Assigned Staff Count',
                'Total Permissions',
                'Status',
                'Role Description',
                'Assigned Capabilities / Permissions',
            ]);

            foreach ($roles as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r->display_name,
                    $r->name,
                    $r->is_system ? 'System Preset' : 'Custom Defined',
                    $r->staff_count,
                    count($r->permissions ?? []),
                    strtoupper($r->status),
                    $r->description,
                    implode(', ', $r->permissions ?? []),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Ensure default ISP standard roles exist for tenant
     */
    protected function ensureDefaultRolesExist(int $tenantId): void
    {
        $count = TenantRole::where('tenant_id', $tenantId)->count();
        if ($count > 0) {
            return;
        }

        $defaults = [
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_admin',
                'display_name' => 'ISP Super Admin',
                'description' => 'Unrestricted full administrative access to all subscriber management, MikroTik/RADIUS network, billing, reports, and security settings.',
                'color' => 'purple',
                'icon' => 'fa-shield-halved',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.status_toggle', 'customers.export',
                    'network.routers', 'network.radius', 'network.olts_onus', 'network.packages', 'network.ip_pools', 'network.disconnect_user',
                    'billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.wholesale', 'billing.gateways', 'billing.expenses', 'billing.ledger',
                    'resellers.view', 'resellers.create', 'resellers.edit', 'resellers.wallet',
                    'support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla',
                    'reports.revenue', 'reports.collection', 'reports.mrtg', 'reports.btrc', 'reports.inventory',
                    'settings.profile', 'settings.sms_gateway', 'settings.automation', 'settings.rbac', 'settings.backup', 'settings.audit_logs'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_manager',
                'display_name' => 'ISP Operations Manager',
                'description' => 'Comprehensive operational management covering subscriber accounts, packages, customer billing, support tickets, and field technician dispatch.',
                'color' => 'blue',
                'icon' => 'fa-user-tie',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'customers.create', 'customers.edit', 'customers.status_toggle', 'customers.export',
                    'network.routers', 'network.packages', 'network.olts_onus', 'network.disconnect_user',
                    'billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.wholesale', 'billing.expenses',
                    'resellers.view', 'resellers.create',
                    'support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla',
                    'reports.revenue', 'reports.collection', 'reports.inventory'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_noc',
                'display_name' => 'NOC & Network Engineer',
                'description' => 'Dedicated network engineering role for MikroTik routers, FreeRADIUS auth, OLT/ONU provisioning, IP pool routing, and MRTG telemetry.',
                'color' => 'indigo',
                'icon' => 'fa-network-wired',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'customers.status_toggle',
                    'network.routers', 'network.radius', 'network.olts_onus', 'network.packages', 'network.ip_pools', 'network.disconnect_user',
                    'support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla',
                    'reports.mrtg', 'reports.inventory', 'reports.btrc'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_collector',
                'display_name' => 'Billing & Cash Collector',
                'description' => 'Field and counter billing collector role for searching subscribers, collecting bill payments, issuing POS/A4 money receipts, and daily cash closing.',
                'color' => 'amber',
                'icon' => 'fa-money-bill-wave',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'billing.invoices', 'billing.collect', 'billing.cash_handover', 'reports.collection'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_support',
                'display_name' => 'Customer Support & Helpdesk',
                'description' => 'Front-desk customer service role for handling customer support inquiries, opening tickets, checking optical signal, and technician booking.',
                'color' => 'cyan',
                'icon' => 'fa-headset',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'network.olts_onus', 'support.tickets', 'support.field_jobs', 'support.installations', 'reports.inventory'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'isp_lineman',
                'display_name' => 'Field Lineman & Fiber Splicer',
                'description' => 'Field technician role for fiber drop cable pulling, core splicing, ONU optical power check, and executing assigned field work orders.',
                'color' => 'emerald',
                'icon' => 'fa-wrench',
                'is_system' => false,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'network.olts_onus', 'support.field_jobs', 'support.installations'
                ],
            ],
            [
                'tenant_id' => $tenantId,
                'name' => 'reseller_admin',
                'display_name' => 'Sub-ISP Reseller Admin',
                'description' => 'Partner sub-ISP role with dedicated wholesale account portal, sub-customer management, and bandwidth recharge controls.',
                'color' => 'rose',
                'icon' => 'fa-building-shield',
                'is_system' => true,
                'status' => 'active',
                'permissions' => [
                    'customers.view', 'customers.create', 'customers.edit', 'customers.status_toggle',
                    'billing.invoices', 'billing.collect', 'billing.wholesale',
                    'support.tickets', 'support.escalations'
                ],
            ],
        ];

        foreach ($defaults as $d) {
            TenantRole::create($d);
        }
    }
}
