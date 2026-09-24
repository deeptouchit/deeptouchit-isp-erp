<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantRoute;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoutingController extends Controller
{
    protected MikrotikApiService $mikrotikApiService;

    public function __construct(MikrotikApiService $mikrotikApiService)
    {
        $this->mikrotikApiService = $mikrotikApiService;
    }

    /**
     * Display a listing of Routing Table records following the 4-tier UI Design Standard.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        $routers = TenantRouter::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address']);

        $query = TenantRoute::where('tenant_id', $tenant->id)->with('router');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dst_address', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('routing_table', 'like', "%{$search}%")
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

        // Routing Table Filter
        if ($table = $request->input('routing_table')) {
            $query->where('routing_table', $table);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 30);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 30;
        }

        $routes = $query->latest('id')->paginate($perPage)->withQueryString();

        // Calculate filter-aware summary metrics
        $metricBase = TenantRoute::where('tenant_id', $tenant->id);

        if ($search = $request->input('search')) {
            $metricBase->where(function ($q) use ($search) {
                $q->where('dst_address', 'like', "%{$search}%")
                  ->orWhere('gateway', 'like', "%{$search}%")
                  ->orWhere('routing_table', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($routerId = $request->input('router_id')) {
            $metricBase->where('router_id', $routerId);
        }

        if ($type = $request->input('type')) {
            $metricBase->where('type', $type);
        }

        $totalRoutes = (clone $metricBase)->count();
        $defaultGateways = (clone $metricBase)->where('dst_address', '0.0.0.0/0')->count();
        $staticRoutes = (clone $metricBase)->where('type', 'static')->count();
        $dynamicRoutes = (clone $metricBase)->whereIn('type', ['bgp', 'ospf', 'connected'])->count();
        $activeRoutes = (clone $metricBase)->where('status', 'active')->count();
        $syncedRoutes = (clone $metricBase)->where('is_sync_mikrotik', true)->count();

        return view('tenant.network.routing', compact(
            'tenant',
            'routers',
            'routes',
            'totalRoutes',
            'defaultGateways',
            'staticRoutes',
            'dynamicRoutes',
            'activeRoutes',
            'syncedRoutes'
        ));
    }

    /**
     * Store a newly created Route in database & MikroTik.
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'dst_address' => 'required|string|max:100',
            'gateway' => 'required|string|max:100',
            'distance' => 'nullable|integer|min:1|max:255',
            'routing_table' => 'nullable|string|max:50',
            'type' => 'required|string|in:static,bgp,ospf,connected,blackhole',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'status' => 'nullable|string|in:active,disabled',
            'description' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $route = TenantRoute::create([
            'tenant_id' => $tenant->id,
            'dst_address' => $validated['dst_address'],
            'gateway' => $validated['gateway'],
            'distance' => $validated['distance'] ?? 1,
            'routing_table' => $validated['routing_table'] ?? 'main',
            'type' => $validated['type'],
            'router_id' => $validated['router_id'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'description' => $validated['description'] ?? null,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? false),
        ]);

        // Push to MikroTik if sync is requested
        if ($route->is_sync_mikrotik && $route->router_id) {
            try {
                $router = TenantRouter::where('tenant_id', $tenant->id)->find($route->router_id);
                if ($router) {
                    $syncRes = $this->mikrotikApiService->pushRoute(
                        $router,
                        $route->dst_address,
                        $route->gateway,
                        (int) $route->distance,
                        $route->routing_table,
                        $route->description,
                        $route->status === 'disabled'
                    );
                    if (!$syncRes['success']) {
                        Log::warning("Failed to push Route {$route->dst_address} to MikroTik: " . ($syncRes['message'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Exception pushing Route {$route->dst_address} to MikroTik: " . $e->getMessage());
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Route '{$route->dst_address}' via {$route->gateway} created successfully." . ($route->is_sync_mikrotik ? ' Synced to MikroTik.' : ''),
                'route' => $route->fresh()->load('router'),
            ]);
        }

        return redirect()->route('tenant.network.routing')->with('success', "Route '{$route->dst_address}' created successfully.");
    }

    /**
     * Display single Route details.
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $route = TenantRoute::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);

        return response()->json([
            'success' => true,
            'route' => $route,
        ]);
    }

    /**
     * Update the specified Route.
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $route = TenantRoute::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'dst_address' => 'required|string|max:100',
            'gateway' => 'required|string|max:100',
            'distance' => 'nullable|integer|min:1|max:255',
            'routing_table' => 'nullable|string|max:50',
            'type' => 'required|string|in:static,bgp,ospf,connected,blackhole',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'status' => 'nullable|string|in:active,disabled',
            'description' => 'nullable|string|max:500',
            'is_sync_mikrotik' => 'nullable|boolean',
        ]);

        $route->update([
            'dst_address' => $validated['dst_address'],
            'gateway' => $validated['gateway'],
            'distance' => $validated['distance'] ?? $route->distance,
            'routing_table' => $validated['routing_table'] ?? $route->routing_table,
            'type' => $validated['type'],
            'router_id' => $validated['router_id'] ?? null,
            'status' => $validated['status'] ?? $route->status,
            'description' => $validated['description'] ?? null,
            'is_sync_mikrotik' => (bool) ($validated['is_sync_mikrotik'] ?? $route->is_sync_mikrotik),
        ]);

        $mikrotikMsg = '';
        // Push update to MikroTik if synced
        if ($route->is_sync_mikrotik && $route->router_id) {
            try {
                $router = TenantRouter::where('tenant_id', $tenant->id)->find($route->router_id);
                if ($router) {
                    $syncRes = $this->mikrotikApiService->pushRoute(
                        $router,
                        $route->dst_address,
                        $route->gateway,
                        (int) $route->distance,
                        $route->routing_table,
                        $route->description,
                        $route->status === 'disabled'
                    );
                    if ($syncRes['success']) {
                        $mikrotikMsg = ' Synced to MikroTik.';
                    } else {
                        Log::warning("Failed to update Route {$route->dst_address} on MikroTik: " . ($syncRes['message'] ?? ''));
                        $mikrotikMsg = ' (MikroTik sync warning: ' . ($syncRes['message'] ?? 'could not reach router') . ')';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Exception updating Route {$route->dst_address} on MikroTik: " . $e->getMessage());
                $mikrotikMsg = ' (MikroTik sync error: ' . $e->getMessage() . ')';
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Route '{$route->dst_address}' updated successfully." . $mikrotikMsg,
                'route' => $route->fresh()->load('router'),
            ]);
        }

        return redirect()->route('tenant.network.routing')->with('success', "Route '{$route->dst_address}' updated successfully." . $mikrotikMsg);
    }

    /**
     * Remove the specified Route from database and MikroTik.
     */
    public function destroy(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $route = TenantRoute::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $dst = $route->dst_address;

        // Clean up from MikroTik if synced
        if ($route->is_sync_mikrotik && $route->router) {
            try {
                $this->mikrotikApiService->removeRoute($route->router, $dst);
            } catch (\Exception $e) {
                Log::warning("Failed to remove Route '{$dst}' from MikroTik on delete: " . $e->getMessage());
            }
        }

        $route->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Route '{$dst}' deleted successfully.",
            ]);
        }

        return redirect()->route('tenant.network.routing')->with('success', "Route '{$dst}' deleted successfully.");
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

        $route = TenantRoute::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $newStatus = $route->status === 'active' ? 'disabled' : 'active';
        $route->update(['status' => $newStatus]);

        if ($route->is_sync_mikrotik && $route->router) {
            try {
                $this->mikrotikApiService->pushRoute(
                    $route->router,
                    $route->dst_address,
                    $route->gateway,
                    (int) $route->distance,
                    $route->routing_table,
                    $route->description,
                    $newStatus === 'disabled'
                );
            } catch (\Throwable $e) {
                Log::warning("Exception toggling Route {$route->dst_address} status on MikroTik: " . $e->getMessage());
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Route '{$route->dst_address}' status changed to {$newStatus}.",
                'status' => $newStatus,
            ]);
        }

        return redirect()->route('tenant.network.routing')->with('success', "Route status updated.");
    }

    /**
     * Push / Sync Route to MikroTik router live via RouterOS API.
     */
    public function syncMikrotik(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $route = TenantRoute::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);

        if (!$route->router) {
            return response()->json([
                'success' => false,
                'message' => 'No MikroTik router is assigned to this Route. Please edit the route and select a Router.',
            ], 422);
        }

        $result = $this->mikrotikApiService->pushRoute(
            $route->router,
            $route->dst_address,
            $route->gateway,
            $route->distance,
            $route->routing_table,
            $route->description,
            $route->status === 'disabled'
        );

        if ($result['success']) {
            $route->update(['is_sync_mikrotik' => true]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'route' => $route->fresh()->load('router'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to communicate with MikroTik router.',
        ], 500);
    }

    /**
     * Import / Synchronize all Routes from MikroTik Router into Software.
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

        $res = $this->mikrotikApiService->pullRoutes($router);
        if (!$res['success']) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'Failed to pull Routes from MikroTik router.',
            ], 500);
        }

        $imported = 0;
        $updated = 0;

        foreach ($res['routes'] as $routeData) {
            $dst = $routeData['dst_address'] ?? '0.0.0.0/0';
            $gateway = !empty($routeData['gateway']) ? $routeData['gateway'] : 'connected';
            $distance = (int) ($routeData['distance'] ?? 1);
            $table = !empty($routeData['routing_table']) ? $routeData['routing_table'] : 'main';
            $type = in_array($routeData['type'] ?? '', ['static', 'bgp', 'ospf', 'connected', 'blackhole']) ? $routeData['type'] : 'static';
            $comment = $routeData['comment'] ?? null;
            $disabled = !empty($routeData['disabled']);

            $route = TenantRoute::where('tenant_id', $tenant->id)
                ->where('router_id', $router->id)
                ->where('dst_address', $dst)
                ->first();

            if ($route) {
                $route->update([
                    'gateway' => $gateway,
                    'distance' => $distance,
                    'routing_table' => $table,
                    'type' => $type,
                    'status' => $disabled ? 'disabled' : 'active',
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $updated++;
            } else {
                TenantRoute::create([
                    'tenant_id' => $tenant->id,
                    'router_id' => $router->id,
                    'dst_address' => $dst,
                    'gateway' => $gateway,
                    'distance' => $distance,
                    'routing_table' => $table,
                    'type' => $type,
                    'status' => $disabled ? 'disabled' : 'active',
                    'description' => $comment,
                    'is_sync_mikrotik' => true,
                ]);
                $imported++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully synchronized with MikroTik ({$router->name}): {$imported} new Routes imported, {$updated} existing Routes updated.",
            'imported_count' => $imported,
            'updated_count' => $updated,
            'total_from_mikrotik' => count($res['routes']),
        ]);
    }
}
