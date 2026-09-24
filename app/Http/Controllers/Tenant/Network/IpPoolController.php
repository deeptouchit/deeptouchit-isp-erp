<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantIpPool;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IpPoolController extends Controller
{
    protected MikrotikApiService $mikrotikApiService;

    public function __construct(MikrotikApiService $mikrotikApiService)
    {
        $this->mikrotikApiService = $mikrotikApiService;
    }
    /**
     * Display a listing of IP Pools & Subnets following the 4-tier UI Design Standard.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        $routers = TenantRouter::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address']);

        $query = TenantIpPool::where('tenant_id', $tenant->id)->with('router');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('range_start', 'like', "%{$search}%")
                  ->orWhere('range_end', 'like', "%{$search}%")
                  ->orWhere('cidr_subnet', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Router Filter
        if ($routerId = $request->input('router_id')) {
            $query->where('router_id', $routerId);
        }

        // Pool Type Filter
        if ($poolType = $request->input('pool_type')) {
            $query->where('pool_type', $poolType);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 30);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 30;
        }

        $pools = $query->latest('id')->paginate($perPage)->withQueryString();

        // Calculate filter-aware summary metrics
        $metricBase = TenantIpPool::where('tenant_id', $tenant->id);

        if ($search = $request->input('search')) {
            $metricBase->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('range_start', 'like', "%{$search}%")
                  ->orWhere('range_end', 'like', "%{$search}%")
                  ->orWhere('cidr_subnet', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($routerId = $request->input('router_id')) {
            $metricBase->where('router_id', $routerId);
        }

        if ($poolType = $request->input('pool_type')) {
            $metricBase->where('pool_type', $poolType);
        }

        $totalPools = (clone $metricBase)->count();
        $totalIps = (int) (clone $metricBase)->sum('total_ips');
        $usedIps = (int) (clone $metricBase)->sum('used_ips');
        $freeIps = max(0, $totalIps - $usedIps);
        $staticPublicIps = (int) (clone $metricBase)->where('pool_type', 'static_public')->sum('total_ips');
        $cgnatIps = (int) (clone $metricBase)->where('pool_type', 'cgnat')->sum('total_ips');
        $utilizationPercent = $totalIps > 0 ? round(($usedIps / $totalIps) * 100, 1) : 0.0;

        return view('tenant.network.ip_pools', compact(
            'tenant',
            'routers',
            'pools',
            'totalPools',
            'totalIps',
            'usedIps',
            'freeIps',
            'staticPublicIps',
            'cgnatIps',
            'utilizationPercent'
        ));
    }

    /**
     * Store a newly created IP Pool in database.
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pool_type' => 'required|string|in:pppoe,cgnat,static_public,dhcp,ipv6,vpn',
            'ip_version' => 'nullable|string|in:ipv4,ipv6',
            'ranges' => 'nullable|string|max:255',
            'range_start' => 'nullable|string|max:100',
            'range_end' => 'nullable|string|max:100',
            'cidr_subnet' => 'nullable|string|max:100',
            'gateway' => 'nullable|string|max:100',
            'dns_primary' => 'nullable|string|max:100',
            'dns_secondary' => 'nullable|string|max:100',
            'next_pool' => 'nullable|string|max:100',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'status' => 'nullable|string|in:active,disabled,exhausted',
            'description' => 'nullable|string|max:500',
            'comment' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $description = $validated['description'] ?? $validated['comment'] ?? $request->input('comment') ?? $request->input('description') ?? null;

        $rangesInput = $validated['ranges'] ?? (($validated['range_start'] ?? '') . (!empty($validated['range_end']) ? '-' . $validated['range_end'] : ''));
        if (empty($rangesInput)) {
            return response()->json(['success' => false, 'message' => 'Please provide IP range addresses (e.g. 10.10.0.2-10.10.3.254).'], 422);
        }

        $parsed = TenantIpPool::parseRange($rangesInput);
        $rangeStart = $parsed['start'];
        $rangeEnd = $parsed['end'];
        $totalIps = $parsed['count'];

        $pool = TenantIpPool::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'pool_type' => $validated['pool_type'],
            'ip_version' => $validated['ip_version'] ?? 'ipv4',
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'cidr_subnet' => $validated['cidr_subnet'] ?? null,
            'gateway' => $validated['gateway'] ?? null,
            'dns_primary' => $validated['dns_primary'] ?? '8.8.8.8',
            'dns_secondary' => $validated['dns_secondary'] ?? '1.1.1.1',
            'next_pool' => $validated['next_pool'] ?? null,
            'router_id' => $validated['router_id'] ?? null,
            'vlan_id' => $validated['vlan_id'] ?? null,
            'total_ips' => $totalIps,
            'used_ips' => 0,
            'status' => $validated['status'] ?? 'active',
            'description' => $description,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? false),
        ]);

        // If sync to MikroTik is requested and router assigned, push immediately
        if ($pool->is_sync_mikrotik && $pool->router_id) {
            $router = TenantRouter::where('tenant_id', $tenant->id)->find($pool->router_id);
            if ($router) {
                $syncRes = $this->mikrotikApiService->pushIpPool($router, $pool->name, $pool->addresses_display, $pool->description, $pool->next_pool);
                if ($syncRes['success'] && isset($syncRes['used_ips'])) {
                    $pool->update(['used_ips' => $syncRes['used_ips']]);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "IP Pool '{$pool->name}' ({$totalIps} IPs) created successfully." . ($pool->is_sync_mikrotik ? ' Synced to MikroTik.' : ''),
                'pool' => $pool->fresh()->load('router'),
            ]);
        }

        return redirect()->route('tenant.network.ip-pools')->with('success', "IP Pool '{$pool->name}' created successfully.");
    }

    /**
     * Display single IP Pool details.
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pool = TenantIpPool::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);

        return response()->json([
            'success' => true,
            'pool' => $pool,
        ]);
    }

    /**
     * Update the specified IP Pool.
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pool = TenantIpPool::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pool_type' => 'required|string|in:pppoe,cgnat,static_public,dhcp,ipv6,vpn',
            'ip_version' => 'nullable|string|in:ipv4,ipv6',
            'ranges' => 'nullable|string|max:255',
            'range_start' => 'nullable|string|max:100',
            'range_end' => 'nullable|string|max:100',
            'cidr_subnet' => 'nullable|string|max:100',
            'gateway' => 'nullable|string|max:100',
            'dns_primary' => 'nullable|string|max:100',
            'dns_secondary' => 'nullable|string|max:100',
            'next_pool' => 'nullable|string|max:100',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'status' => 'nullable|string|in:active,disabled,exhausted',
            'description' => 'nullable|string|max:500',
            'comment' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $description = $request->has('description')
            ? $request->input('description')
            : ($request->has('comment') ? $request->input('comment') : $pool->description);

        $rangesInput = $validated['ranges'] ?? (($validated['range_start'] ?? $pool->range_start) . '-' . ($validated['range_end'] ?? $pool->range_end));
        $parsed = TenantIpPool::parseRange($rangesInput);
        $rangeStart = $parsed['start'] ?: $pool->range_start;
        $rangeEnd = $parsed['end'] ?: $pool->range_end;
        $totalIps = $parsed['count'] ?: $pool->total_ips;

        $pool->update([
            'name' => $validated['name'],
            'pool_type' => $validated['pool_type'],
            'ip_version' => $validated['ip_version'] ?? $pool->ip_version,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
            'cidr_subnet' => $validated['cidr_subnet'] ?? null,
            'gateway' => $validated['gateway'] ?? null,
            'dns_primary' => $validated['dns_primary'] ?? '8.8.8.8',
            'dns_secondary' => $validated['dns_secondary'] ?? '1.1.1.1',
            'next_pool' => $validated['next_pool'] ?? null,
            'router_id' => $validated['router_id'] ?? null,
            'vlan_id' => $validated['vlan_id'] ?? null,
            'total_ips' => $totalIps,
            'status' => $validated['status'] ?? $pool->status,
            'description' => $description,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? $pool->is_sync_mikrotik),
        ]);

        // If synced, push update to MikroTik
        if ($pool->is_sync_mikrotik && $pool->router_id) {
            $router = TenantRouter::where('tenant_id', $tenant->id)->find($pool->router_id);
            if ($router) {
                $syncRes = $this->mikrotikApiService->pushIpPool($router, $pool->name, $pool->addresses_display, $pool->description, $pool->next_pool);
                if ($syncRes['success'] && isset($syncRes['used_ips'])) {
                    $pool->update(['used_ips' => $syncRes['used_ips']]);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "IP Pool '{$pool->name}' updated successfully.",
                'pool' => $pool->fresh()->load('router'),
            ]);
        }

        return redirect()->route('tenant.network.ip-pools')->with('success', "IP Pool '{$pool->name}' updated successfully.");
    }

    /**
     * Remove the specified IP Pool from database and MikroTik.
     */
    public function destroy(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pool = TenantIpPool::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $name = $pool->name;

        // If synced to MikroTik, clean up pool from router
        if ($pool->is_sync_mikrotik && $pool->router) {
            try {
                $this->mikrotikApiService->removeIpPool($pool->router, $name);
            } catch (\Exception $e) {
                Log::warning("Failed to remove IP pool '{$name}' from MikroTik on delete: " . $e->getMessage());
            }
        }

        $pool->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "IP Pool '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('tenant.network.ip-pools')->with('success', "IP Pool '{$name}' deleted successfully.");
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

        $pool = TenantIpPool::where('tenant_id', $tenant->id)->findOrFail($id);
        $newStatus = $pool->status === 'active' ? 'disabled' : 'active';
        $pool->update(['status' => $newStatus]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "IP Pool '{$pool->name}' status changed to {$newStatus}.",
                'status' => $newStatus,
            ]);
        }

        return redirect()->route('tenant.network.ip-pools')->with('success', "IP Pool status updated.");
    }

    /**
     * Push / Sync IP Pool to MikroTik router live via RouterOS API.
     */
    public function syncMikrotik(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $pool = TenantIpPool::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);

        if (!$pool->router) {
            return response()->json([
                'success' => false,
                'message' => 'No MikroTik router is assigned to this IP pool. Please edit the pool and select a Router.',
            ], 422);
        }

        $result = $this->mikrotikApiService->pushIpPool($pool->router, $pool->name, $pool->addresses_display, $pool->description, $pool->next_pool);

        if ($result['success']) {
            $pool->update([
                'is_sync_mikrotik' => true,
                'used_ips' => $result['used_ips'] ?? $pool->used_ips,
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'pool' => $pool->fresh()->load('router'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to communicate with MikroTik router.',
        ], 500);
    }

    /**
     * Import / Synchronize all IP Pools from MikroTik Router into Software.
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

        $res = $this->mikrotikApiService->pullIpPools($router);
        if (!$res['success']) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to pull IP pools from MikroTik router.',
            ], 500);
        }

        $imported = 0;
        $updated = 0;

        foreach ($res['pools'] as $poolData) {
            $name = $poolData['name'];
            $ranges = $poolData['ranges'];
            $usedIps = $poolData['used_ips'] ?? 0;
            $comment = $poolData['comment'] ?? null;
            $nextPool = $poolData['next_pool'] ?? null;

            // Parse range (supports 10.10.0.0/22, 10.0.0.2-10.0.0.254, or single IP)
            $parsed = TenantIpPool::parseRange($ranges);
            $rangeStart = $parsed['start'];
            $rangeEnd = $parsed['end'];
            $totalIps = $parsed['count'];

            // Determine pool type intelligently based on name/range
            $type = 'pppoe';
            $lowerName = strtolower($name);
            if (str_contains($lowerName, 'cgnat') || str_contains($lowerName, 'nat')) {
                $type = 'cgnat';
            } elseif (str_contains($lowerName, 'static') || str_contains($lowerName, 'public') || str_contains($lowerName, 'real')) {
                $type = 'static_public';
            } elseif (str_contains($lowerName, 'dhcp') || str_contains($lowerName, 'hotspot')) {
                $type = 'dhcp';
            }

            $pool = TenantIpPool::where('tenant_id', $tenant->id)
                ->where('router_id', $router->id)
                ->where('name', $name)
                ->first();

            if ($pool) {
                $pool->update([
                    'range_start' => $rangeStart,
                    'range_end' => $rangeEnd,
                    'total_ips' => $totalIps,
                    'used_ips' => $usedIps,
                    'next_pool' => $nextPool,
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $updated++;
            } else {
                TenantIpPool::create([
                    'tenant_id' => $tenant->id,
                    'router_id' => $router->id,
                    'name' => $name,
                    'pool_type' => $type,
                    'ip_version' => 'ipv4',
                    'range_start' => $rangeStart,
                    'range_end' => $rangeEnd,
                    'total_ips' => $totalIps,
                    'used_ips' => $usedIps,
                    'next_pool' => $nextPool,
                    'status' => 'active',
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $imported++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synchronized with MikroTik ({$router->name}): {$imported} new pools imported, {$updated} existing pools updated.",
            'imported_count' => $imported,
            'updated_count' => $updated,
            'total_from_mikrotik' => count($res['pools']),
        ]);
    }
}
