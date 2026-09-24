<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantOlt;
use App\Models\TenantRouter;
use App\Models\TenantVlan;
use App\Services\Network\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VlanController extends Controller
{
    protected MikrotikApiService $mikrotikApiService;

    public function __construct(MikrotikApiService $mikrotikApiService)
    {
        $this->mikrotikApiService = $mikrotikApiService;
    }

    /**
     * Display a listing of VLANs following the 4-tier UI Design Standard.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        $routers = TenantRouter::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address']);
        $olts = TenantOlt::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address']);

        $query = TenantVlan::where('tenant_id', $tenant->id)->with(['router', 'olt']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vlan_id', 'like', "%{$search}%")
                  ->orWhere('interface', 'like', "%{$search}%")
                  ->orWhere('subnet', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Router Filter
        if ($routerId = $request->input('router_id')) {
            $query->where('router_id', $routerId);
        }

        // Type Filter
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 30);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 30;
        }

        $vlans = $query->latest('id')->paginate($perPage)->withQueryString();

        // Calculate filter-aware summary metrics
        $metricBase = TenantVlan::where('tenant_id', $tenant->id);

        if ($search = $request->input('search')) {
            $metricBase->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vlan_id', 'like', "%{$search}%")
                  ->orWhere('interface', 'like', "%{$search}%")
                  ->orWhere('subnet', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($routerId = $request->input('router_id')) {
            $metricBase->where('router_id', $routerId);
        }

        if ($type = $request->input('type')) {
            $metricBase->where('type', $type);
        }

        $totalVlans = (clone $metricBase)->count();
        $serviceVlans = (clone $metricBase)->where('type', 'service')->count();
        $mgmtVlans = (clone $metricBase)->where('type', 'management')->count();
        $corporateVlans = (clone $metricBase)->where('type', 'corporate')->count();
        $activeVlans = (clone $metricBase)->where('status', 'active')->count();
        $syncedVlans = (clone $metricBase)->where('is_sync_mikrotik', true)->count();

        return view('tenant.network.vlans', compact(
            'tenant',
            'routers',
            'olts',
            'vlans',
            'totalVlans',
            'serviceVlans',
            'mgmtVlans',
            'corporateVlans',
            'activeVlans',
            'syncedVlans'
        ));
    }

    /**
     * Store a newly created VLAN in database & MikroTik.
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'vlan_id' => 'required|integer|min:1|max:4094',
            'name' => 'required|string|max:100',
            'interface' => 'required|string|max:100',
            'type' => 'required|string|in:service,management,corporate,cgnat,voice,tr069',
            'subnet' => 'nullable|string|max:100',
            'gateway' => 'nullable|string|max:100',
            'dhcp_enabled' => 'nullable|boolean',
            'mtu' => 'nullable|integer|min:576|max:9216',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'olt_id' => 'nullable|exists:tenant_olts,id',
            'status' => 'nullable|string|in:active,disabled',
            'description' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $vlan = TenantVlan::create([
            'tenant_id' => $tenant->id,
            'vlan_id' => $validated['vlan_id'],
            'name' => $validated['name'],
            'interface' => $validated['interface'],
            'type' => $validated['type'],
            'subnet' => $validated['subnet'] ?? null,
            'gateway' => $validated['gateway'] ?? null,
            'dhcp_enabled' => (bool) ($validated['dhcp_enabled'] ?? false),
            'mtu' => $validated['mtu'] ?? 1500,
            'router_id' => $validated['router_id'] ?? null,
            'olt_id' => $validated['olt_id'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'description' => $validated['description'] ?? null,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? false),
        ]);

        // Push to MikroTik if sync is requested
        if ($vlan->is_sync_mikrotik && $vlan->router_id) {
            $router = TenantRouter::where('tenant_id', $tenant->id)->find($vlan->router_id);
            if ($router) {
                $syncRes = $this->mikrotikApiService->pushVlan(
                    $router,
                    $vlan->name,
                    $vlan->vlan_id,
                    $vlan->interface,
                    $vlan->description,
                    $vlan->status === 'disabled',
                    $vlan->mtu
                );
                if (!$syncRes['success']) {
                    Log::warning("Failed to push VLAN {$vlan->name} to MikroTik: " . ($syncRes['message'] ?? ''));
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "VLAN '{$vlan->name}' (ID: {$vlan->vlan_id}) created successfully." . ($vlan->is_sync_mikrotik ? ' Synced to MikroTik.' : ''),
                'vlan' => $vlan->fresh()->load(['router', 'olt']),
            ]);
        }

        return redirect()->route('tenant.network.vlans')->with('success', "VLAN '{$vlan->name}' created successfully.");
    }

    /**
     * Display single VLAN details.
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $vlan = TenantVlan::where('tenant_id', $tenant->id)->with(['router', 'olt'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'vlan' => $vlan,
        ]);
    }

    /**
     * Update the specified VLAN.
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $vlan = TenantVlan::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'vlan_id' => 'required|integer|min:1|max:4094',
            'name' => 'required|string|max:100',
            'interface' => 'required|string|max:100',
            'type' => 'required|string|in:service,management,corporate,cgnat,voice,tr069',
            'subnet' => 'nullable|string|max:100',
            'gateway' => 'nullable|string|max:100',
            'dhcp_enabled' => 'nullable|boolean',
            'mtu' => 'nullable|integer|min:576|max:9216',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'olt_id' => 'nullable|exists:tenant_olts,id',
            'status' => 'nullable|string|in:active,disabled',
            'description' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $vlan->update([
            'vlan_id' => $validated['vlan_id'],
            'name' => $validated['name'],
            'interface' => $validated['interface'],
            'type' => $validated['type'],
            'subnet' => $validated['subnet'] ?? null,
            'gateway' => $validated['gateway'] ?? null,
            'dhcp_enabled' => (bool) ($validated['dhcp_enabled'] ?? false),
            'mtu' => $validated['mtu'] ?? $vlan->mtu,
            'router_id' => $validated['router_id'] ?? null,
            'olt_id' => $validated['olt_id'] ?? null,
            'status' => $validated['status'] ?? $vlan->status,
            'description' => $validated['description'] ?? null,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? $vlan->is_sync_mikrotik),
        ]);

        // Push update to MikroTik if synced
        if ($vlan->is_sync_mikrotik && $vlan->router_id) {
            $router = TenantRouter::where('tenant_id', $tenant->id)->find($vlan->router_id);
            if ($router) {
                $syncRes = $this->mikrotikApiService->pushVlan(
                    $router,
                    $vlan->name,
                    $vlan->vlan_id,
                    $vlan->interface,
                    $vlan->description,
                    $vlan->status === 'disabled',
                    $vlan->mtu
                );
                if (!$syncRes['success']) {
                    Log::warning("Failed to update VLAN {$vlan->name} on MikroTik: " . ($syncRes['message'] ?? ''));
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "VLAN '{$vlan->name}' (ID: {$vlan->vlan_id}) updated successfully.",
                'vlan' => $vlan->fresh()->load(['router', 'olt']),
            ]);
        }

        return redirect()->route('tenant.network.vlans')->with('success', "VLAN '{$vlan->name}' updated successfully.");
    }

    /**
     * Remove the specified VLAN from database and MikroTik.
     */
    public function destroy(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $vlan = TenantVlan::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $name = $vlan->name;

        // Clean up from MikroTik if synced
        if ($vlan->is_sync_mikrotik && $vlan->router) {
            try {
                $this->mikrotikApiService->removeVlan($vlan->router, $name);
            } catch (\Exception $e) {
                Log::warning("Failed to remove VLAN '{$name}' from MikroTik on delete: " . $e->getMessage());
            }
        }

        $vlan->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "VLAN '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('tenant.network.vlans')->with('success', "VLAN '{$name}' deleted successfully.");
    }

    /**
     * Toggle status between active and disabled.
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $vlan = TenantVlan::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $newStatus = $vlan->status === 'active' ? 'disabled' : 'active';
        $vlan->update(['status' => $newStatus]);

        if ($vlan->is_sync_mikrotik && $vlan->router) {
            $this->mikrotikApiService->pushVlan(
                $vlan->router,
                $vlan->name,
                $vlan->vlan_id,
                $vlan->interface,
                $vlan->description,
                $newStatus === 'disabled',
                $vlan->mtu
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "VLAN '{$vlan->name}' status changed to {$newStatus}.",
                'status' => $newStatus,
            ]);
        }

        return redirect()->route('tenant.network.vlans')->with('success', "VLAN status updated.");
    }

    /**
     * Push / Sync VLAN to MikroTik router live via RouterOS API.
     */
    public function syncMikrotik(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $vlan = TenantVlan::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);

        if (!$vlan->router) {
            return response()->json([
                'success' => false,
                'message' => 'No MikroTik router is assigned to this VLAN. Please edit the VLAN and select a Router.',
            ], 422);
        }

        $result = $this->mikrotikApiService->pushVlan(
            $vlan->router,
            $vlan->name,
            $vlan->vlan_id,
            $vlan->interface,
            $vlan->description,
            $vlan->status === 'disabled',
            $vlan->mtu
        );

        if ($result['success']) {
            $vlan->update(['is_sync_mikrotik' => true]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'vlan' => $vlan->fresh()->load(['router', 'olt']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to communicate with MikroTik router.',
        ], 500);
    }

    /**
     * Import / Synchronize all VLANs from MikroTik Router into Software.
     */
    public function importFromMikrotik(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'router_id' => 'required|exists:tenant_routers,id',
        ]);

        $router = TenantRouter::where('tenant_id', $tenant->id)->findOrFail($validated['router_id']);

        $res = $this->mikrotikApiService->pullVlans($router);
        if (!$res['success']) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to pull VLANs from MikroTik router.',
            ], 500);
        }

        $imported = 0;
        $updated = 0;

        foreach ($res['vlans'] as $vlanData) {
            $name = $vlanData['name'];
            $vlanId = $vlanData['vlan_id'];
            $interface = $vlanData['interface'];
            $comment = $vlanData['comment'] ?? null;
            $disabled = $vlanData['disabled'] ?? false;
            $mtu = $vlanData['mtu'] ?? 1500;

            // Intelligent VLAN Type detection
            $type = 'service';
            $lowerName = strtolower($name . ' ' . ($comment ?? ''));
            if (str_contains($lowerName, 'mgmt') || str_contains($lowerName, 'management') || str_contains($lowerName, 'olt') || str_contains($lowerName, 'switch')) {
                $type = 'management';
            } elseif (str_contains($lowerName, 'corp') || str_contains($lowerName, 'corporate') || str_contains($lowerName, 'lease') || str_contains($lowerName, 'dedicated')) {
                $type = 'corporate';
            } elseif (str_contains($lowerName, 'cgnat') || str_contains($lowerName, 'nat')) {
                $type = 'cgnat';
            } elseif (str_contains($lowerName, 'voice') || str_contains($lowerName, 'voip') || str_contains($lowerName, 'sip')) {
                $type = 'voice';
            } elseif (str_contains($lowerName, 'tr069') || str_contains($lowerName, 'acs')) {
                $type = 'tr069';
            }

            $vlan = TenantVlan::where('tenant_id', $tenant->id)
                ->where('router_id', $router->id)
                ->where('name', $name)
                ->first();

            if ($vlan) {
                $vlan->update([
                    'vlan_id' => $vlanId,
                    'interface' => $interface,
                    'mtu' => $mtu,
                    'status' => $disabled ? 'disabled' : 'active',
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $updated++;
            } else {
                TenantVlan::create([
                    'tenant_id' => $tenant->id,
                    'router_id' => $router->id,
                    'name' => $name,
                    'vlan_id' => $vlanId,
                    'interface' => $interface,
                    'type' => $type,
                    'mtu' => $mtu,
                    'status' => $disabled ? 'disabled' : 'active',
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $imported++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synchronized with MikroTik ({$router->name}): {$imported} new VLANs imported, {$updated} existing VLANs updated.",
            'imported_count' => $imported,
            'updated_count' => $updated,
            'total_from_mikrotik' => count($res['vlans']),
        ]);
    }

    /**
     * Get available interfaces for a specific router (for dynamic form populate).
     */
    public function getRouterInterfaces(Request $request, $routerId)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'interfaces' => []], 401);
        }

        $router = TenantRouter::where('tenant_id', $tenant->id)->find($routerId);
        if (!$router) {
            return response()->json(['success' => false, 'interfaces' => ['ether1', 'ether2', 'ether3', 'bridge']]);
        }

        $interfaces = $this->mikrotikApiService->getInterfaceNames($router);

        return response()->json([
            'success' => true,
            'interfaces' => $interfaces,
        ]);
    }
}
