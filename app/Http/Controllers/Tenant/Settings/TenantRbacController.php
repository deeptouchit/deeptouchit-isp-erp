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
            abort(404, __('ISP Tenant record not found.'));
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
        $currencySymbol = $tenant->currency_symbol ?? '৳';

        // Auto-seed default standard ISP roles if none exist for this tenant
        $this->ensureDefaultRolesExist($tenantId);

        $search = $request->input('search');
        $typeFilter = $request->input('type'); // 'all', 'system', 'custom'
        $statusFilter = $request->input('status'); // 'all', 'active', 'disabled'
        $scopeFilter = $request->input('scope', 'all'); // 'all', 'customers', 'network', 'billing', 'support', 'settings'
        $perPage = (int)$request->input('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        // Active more filters counter for 2-Tier Filter System
        $activeMoreCount = 0;
        if ($scopeFilter !== 'all' && !empty($scopeFilter)) $activeMoreCount++;
        $isMoreOpen = $activeMoreCount > 0;

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

        if ($scopeFilter && $scopeFilter !== 'all') {
            $query->where(function ($q) use ($scopeFilter) {
                $q->where('name', 'isp_admin')
                  ->orWhere('permissions', 'like', "%\"{$scopeFilter}.%");
            });
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
            'currencySymbol',
            'roles',
            'permissionCatalog',
            'stats',
            'search',
            'typeFilter',
            'statusFilter',
            'scopeFilter',
            'perPage',
            'activeMoreCount',
            'isMoreOpen'
        ));
    }

    /**
     * Display standalone dedicated page to create a new role
     */
    public function create(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $permissionCatalog = TenantRole::getPermissionCatalog();

        $totalPermissions = 0;
        foreach ($permissionCatalog as $group) {
            $totalPermissions += count($group['permissions']);
        }

        return view('tenant.settings.rbac_create', compact(
            'tenant',
            'currencySymbol',
            'permissionCatalog',
            'totalPermissions'
        ));
    }

    /**
     * Display standalone dedicated page to edit an existing role
     */
    public function edit($id): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);
        $permissionCatalog = TenantRole::getPermissionCatalog();

        $totalPermissions = 0;
        foreach ($permissionCatalog as $group) {
            $totalPermissions += count($group['permissions']);
        }

        $assignedStaff = User::where('tenant_id', $tenant->id)
            ->where('role', $role->name)
            ->select('id', 'name', 'email', 'phone', 'mobile', 'staff_id', 'designation', 'status')
            ->get();

        return view('tenant.settings.rbac_edit', compact(
            'tenant',
            'currencySymbol',
            'role',
            'permissionCatalog',
            'totalPermissions',
            'assignedStaff'
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
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'RBAC_ROLE_CREATED',
            'description' => __('Created custom RBAC role \':display_name\' (:name) with :count permissions.', [
                'display_name' => $role->display_name,
                'name' => $role->name,
                'count' => count($role->permissions ?? []),
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('tenant.settings.rbac')->with('success', __('Role \':name\' created successfully.', ['name' => __($role->display_name)]));
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

        $badge = $role->badge;
        $badge['label'] = __($badge['label']);

        return response()->json([
            'success' => true,
            'role' => $role,
            'assigned_staff' => $assignedStaff,
            'badge' => $badge,
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
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'RBAC_ROLE_UPDATED',
            'description' => __('Updated RBAC role \':display_name\' (:name) permissions matrix (:count capabilities).', [
                'display_name' => $role->display_name,
                'name' => $role->name,
                'count' => count($role->permissions ?? []),
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('tenant.settings.rbac')->with('success', __('Role \':name\' updated successfully.', ['name' => __($role->display_name)]));
    }

    /**
     * Clone an existing role into a new custom role
     */
    public function cloneRole(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $sourceRole = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        $newName = __(':name (Copy)', ['name' => __($sourceRole->display_name)]);
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
            'description' => trim(__('Cloned from \':name\'.', ['name' => __($sourceRole->display_name)]) . ($sourceRole->description ? ' ' . __($sourceRole->description) : '')),
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
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'RBAC_ROLE_CLONED',
            'description' => __('Cloned RBAC role \':source\' into \':target\'.', [
                'source' => $sourceRole->display_name,
                'target' => $cloned->display_name,
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __('Role \':source\' successfully duplicated as \':target\'.', [
            'source' => __($sourceRole->display_name),
            'target' => $cloned->display_name,
        ]));
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
                    'message' => __('The ISP Super Admin system role cannot be disabled.'),
                ], 422);
            }
            return back()->with('error', __('The ISP Super Admin system role cannot be disabled.'));
        }

        $role->status = $role->status === 'active' ? 'disabled' : 'active';
        $role->save();

        $logMessage = $role->status === 'active'
            ? __('Role \':name\' is now enabled.', ['name' => __($role->display_name)])
            : __('Role \':name\' is now disabled.', ['name' => __($role->display_name)]);

        $flashMessage = $role->status === 'active'
            ? __('Role \':name\' has been enabled successfully.', ['name' => __($role->display_name)])
            : __('Role \':name\' has been disabled successfully.', ['name' => __($role->display_name)]);

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'RBAC_ROLE_STATUS_TOGGLED',
            'description' => $logMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'status' => $role->status,
                'message' => $logMessage,
            ]);
        }

        return back()->with('success', $flashMessage);
    }

    /**
     * Delete Custom Role
     */
    public function destroy(Request $request, $id): RedirectResponse
    {
        $tenant = $this->getTenant();
        $role = TenantRole::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($role->name === 'isp_admin') {
            return back()->with('error', __('Primary ISP Super Admin (\':name\') role is protected and cannot be deleted.', ['name' => __($role->display_name)]));
        }

        $staffCount = User::where('tenant_id', $tenant->id)->where('role', $role->name)->count();
        if ($staffCount > 0) {
            return back()->with('error', __('Cannot delete role \':name\' because :count active employees are currently assigned to it. Please reassign them first.', [
                'name' => __($role->display_name),
                'count' => $staffCount,
            ]));
        }

        $roleName = $role->display_name;
        $role->delete();

        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id() ?? 1,
            'actor_name' => Auth::user()?->name ?? __('Admin'),
            'event_type' => 'RBAC_ROLE_DELETED',
            'description' => __('Deleted custom RBAC role \':name\'.', ['name' => $roleName]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', __('Custom role \':name\' deleted successfully.', ['name' => __($roleName)]));
    }

    /**
     * Dedicated A4 Printable Role-Based Access Control Matrix Statement
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $currencySymbol = $tenant->currency_symbol ?? '৳';
        $roles = TenantRole::where('tenant_id', $tenant->id)->orderBy('is_system', 'desc')->get();
        $permissionCatalog = TenantRole::getPermissionCatalog();

        $stats = [
            'total_roles' => $roles->count(),
            'system_roles' => $roles->where('is_system', true)->count(),
            'custom_roles' => $roles->where('is_system', false)->count(),
            'assigned_staff' => User::where('tenant_id', $tenant->id)->where('role', '!=', 'owner')->count(),
        ];

        return view('tenant.settings.rbac_print', compact('tenant', 'currencySymbol', 'roles', 'permissionCatalog', 'stats'));
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
            fputcsv($handle, [__('ROLE-BASED ACCESS CONTROL (RBAC) & PERMISSIONS AUDIT MATRIX')]);
            fputcsv($handle, [__('Generated At'), now()->translatedFormat('d-M-Y h:i:s A')]);
            fputcsv($handle, []);

            // Columns
            fputcsv($handle, [
                __('SL'),
                __('Role Display Name'),
                __('System Key / Slug'),
                __('Classification'),
                __('Assigned Staff Count'),
                __('Total Permissions'),
                __('Status'),
                __('Role Description'),
                __('Assigned Capabilities / Permissions'),
            ]);

            foreach ($roles as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    __($r->badge['label']),
                    $r->name,
                    $r->is_system ? __('System Preset') : __('Custom Defined'),
                    $r->staff_count,
                    count($r->permissions ?? []),
                    $r->status === 'active' ? __('ACTIVE') : ($r->status === 'disabled' ? __('DISABLED') : strtoupper($r->status)),
                    $r->description ? __($r->description) : '',
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
