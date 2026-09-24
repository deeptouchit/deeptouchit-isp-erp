<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\NetworkVaultService;
use App\Services\Network\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MikrotikController extends Controller
{
    protected MikrotikApiService $apiService;
    protected RadiusService $radiusService;

    public function __construct(MikrotikApiService $apiService, RadiusService $radiusService)
    {
        $this->apiService = $apiService;
        $this->radiusService = $radiusService;
    }

    /**
     * Helper to get tenant with ownership check.
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
     * Find router belonging to active tenant.
     */
    protected function findTenantRouter($id): TenantRouter
    {
        $tenant = $this->getTenant();
        return TenantRouter::where('tenant_id', $tenant->id)->findOrFail($id);
    }

    /**
     * Resolve public server IP / domain for client script generation.
     */
    protected function resolveServerPublicIp(): string
    {
        $host = request()->getHost();
        if ($host && !in_array($host, ['127.0.0.1', 'localhost', '10.70.0.1', '10.70.0.2'])) {
            return $host;
        }
        return '103.59.177.136';
    }

    /**
     * Display a listing of MikroTik Routers.
     */
    public function index(Request $request)
    {
        $tenant = $this->getTenant();
        $query = TenantRouter::where('tenant_id', $tenant->id)->withCount(['olts', 'nasClients']);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') && in_array($request->status, ['online', 'offline', 'error', 'pending'])) {
            $query->where('status', $request->status);
        }

        // Active / Enabled Filter
        if ($request->filled('active')) {
            if ($request->active === '1' || $request->active === 'active') {
                $query->where('is_active', true);
            } elseif ($request->active === '0' || $request->active === 'disabled') {
                $query->where('is_active', false);
            }
        }

        // Connection Type Filter
        if ($request->filled('connection_type') && in_array($request->connection_type, ['api', 'radius', 'hybrid'])) {
            $query->where('connection_type', $request->connection_type);
        }

        $perPage = (int)$request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $routers = $query->latest('id')->paginate($perPage)->withQueryString();

        // High-level fleet metrics
        $totalRouters = TenantRouter::where('tenant_id', $tenant->id)->count();
        $activeRouters = TenantRouter::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        $disabledRouters = TenantRouter::where('tenant_id', $tenant->id)->where('is_active', false)->count();
        $onlineRouters = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $offlineRouters = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'offline')->count();
        $avgCpu = TenantRouter::where('tenant_id', $tenant->id)->whereNotNull('cpu_load')->avg('cpu_load');
        $serverIp = $this->resolveServerPublicIp();

        // Plan Quota Resolution
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $mikrotikQuota = $plan ? (int)$plan->mikrotik_limit : 2;
        $quotaRemaining = max(0, $mikrotikQuota - $totalRouters);
        $isQuotaReached = ($mikrotikQuota > 0 && $totalRouters >= $mikrotikQuota);

        return view('tenant.network.mikrotik', compact(
            'tenant',
            'plan',
            'routers',
            'totalRouters',
            'activeRouters',
            'disabledRouters',
            'onlineRouters',
            'offlineRouters',
            'avgCpu',
            'serverIp',
            'mikrotikQuota',
            'quotaRemaining',
            'isQuotaReached'
        ));
    }

    /**
     * Store a newly created MikroTik router.
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();

        // Enforce ISP Tenant Plan MikroTik Quota
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $mikrotikQuota = $plan ? (int)$plan->mikrotik_limit : 2;
        $currentCount = TenantRouter::where('tenant_id', $tenant->id)->count();

        if ($mikrotikQuota > 0 && $currentCount >= $mikrotikQuota) {
            $planName = $plan ? $plan->name : 'Current Plan';
            $msg = "You have reached your Plan ({$planName}) quota limit of {$mikrotikQuota} MikroTik routers. Please upgrade your subscription plan to add more routers.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 403);
            }
            return back()->withErrors(['plan' => $msg])->withInput();
        }
        $mode = $request->input('connection_type', 'radius');

        $rules = [
            'name' => 'required|string|max:100',
            'ip_address' => 'required|string|max:255',
            'connection_type' => 'required|in:api,radius,hybrid',
            'model' => 'nullable|string|max:255',
            'coa_port' => 'nullable|integer|min:1|max:65535',
            'is_active' => 'nullable|boolean',
        ];

        if ($mode === 'radius' || $mode === 'hybrid') {
            $rules['radius_secret'] = 'required|string|max:255';
        }

        if ($mode === 'api' || $mode === 'hybrid') {
            $rules['api_port'] = 'required|integer|min:1|max:65535';
            $rules['username'] = 'required|string|max:100';
            $rules['password'] = 'required|string|max:255';
            $rules['use_ssl'] = 'nullable|boolean';
        } else {
            $rules['api_port'] = 'nullable|integer';
            $rules['username'] = 'nullable|string|max:100';
            $rules['password'] = 'nullable|string|max:255';
            $rules['use_ssl'] = 'nullable|boolean';
        }

        $validated = $request->validate($rules);

        $validated['tenant_id'] = $tenant->id;
        $validated['model'] = $request->input('model');
        $validated['coa_port'] = $request->input('coa_port', 3799);
        $validated['api_port'] = ($mode === 'radius') ? null : ($validated['api_port'] ?? 8728);
        $validated['username'] = ($mode === 'radius') ? null : ($validated['username'] ?? 'admin');
        $validated['use_ssl'] = $request->boolean('use_ssl');
        $validated['status'] = $validated['is_active'] ? 'online' : 'offline';

        $router = TenantRouter::create($validated);

        // Synchronize FreeRADIUS NAS table
        $this->radiusService->syncNasClients($tenant);

        // Attempt immediate background / instant test if requested and active
        if ($validated['is_active'] && $request->boolean('test_immediately') && $mode !== 'radius') {
            $this->apiService->syncRouter($router);
        }

        return redirect()->route('tenant.network.mikrotik')
            ->with('success', "MikroTik Gateway '{$router->name}' added successfully.");
    }

    /**
     * Deep-dive show view with real-time diagnostics, interfaces, and scripts.
     */
    public function show($id)
    {
        $tenant = $this->getTenant();
        $router = $this->findTenantRouter($id);

        $bootstrapScript = NetworkVaultService::generateMikrotikBootstrapScript(
            $tenant->slug,
            $router->name,
            $router->username ?: 'somitysoft_api',
            $router->decrypted_password ?: 'API_PASSWORD',
            $router->api_port ?: 8728,
            $router->decrypted_radius_secret ?: null
        );

        $router->load(['olts', 'nasClients']);
        $ipPools = \App\Models\TenantIpPool::where('tenant_id', $tenant->id)->where('router_id', $router->id)->get();
        $packages = \App\Models\TenantInternetPackage::where('tenant_id', $tenant->id)->where('router_id', $router->id)->get();
        $serverIp = $this->resolveServerPublicIp();
        $radiusCliScript = $router->generateMikrotikRadiusScript($serverIp);

        return view('tenant.network.mikrotik_show', compact(
            'tenant', 
            'router', 
            'bootstrapScript', 
            'radiusCliScript',
            'ipPools',
            'packages',
            'serverIp'
        ));
    }

    /**
     * Update an existing MikroTik Router.
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $router = $this->findTenantRouter($id);
        $mode = $request->input('connection_type', $router->connection_type ?: 'radius');

        $rules = [
            'name' => 'required|string|max:100',
            'ip_address' => 'required|string|max:255',
            'connection_type' => 'required|in:api,radius,hybrid',
            'model' => 'nullable|string|max:255',
            'coa_port' => 'nullable|integer|min:1|max:65535',
            'is_active' => 'nullable|boolean',
        ];

        if ($mode === 'radius' || $mode === 'hybrid') {
            $rules['radius_secret'] = 'required|string|max:255';
        }

        if ($mode === 'api' || $mode === 'hybrid') {
            $rules['api_port'] = 'required|integer|min:1|max:65535';
            $rules['username'] = 'required|string|max:100';
            $rules['password'] = 'nullable|string|max:255';
            $rules['use_ssl'] = 'nullable|boolean';
        } else {
            $rules['api_port'] = 'nullable|integer';
            $rules['username'] = 'nullable|string|max:100';
            $rules['password'] = 'nullable|string|max:255';
            $rules['use_ssl'] = 'nullable|boolean';
        }

        $validated = $request->validate($rules);

        $router->name = $validated['name'];
        $router->ip_address = $validated['ip_address'];
        $router->connection_type = $validated['connection_type'];
        $router->model = $request->input('model');
        $router->coa_port = $request->input('coa_port', $router->coa_port ?: 3799);

        if ($mode !== 'radius') {
            $router->api_port = $validated['api_port'] ?? ($router->api_port ?: 8728);
            $router->username = $validated['username'] ?? ($router->username ?: 'admin');
            $router->use_ssl = $request->boolean('use_ssl');
        } else {
            $router->api_port = null;
        }

        if (!empty($validated['radius_secret'])) {
            $router->radius_secret = $validated['radius_secret'];
        }

        if (!empty($validated['password'])) {
            $router->password = $validated['password'];
        }

        if ($request->has('is_active')) {
            $router->is_active = $request->boolean('is_active');
            if (!$router->is_active) {
                $router->status = 'offline';
            }
        }

        $router->save();

        // Synchronize FreeRADIUS NAS table
        $this->radiusService->syncNasClients($tenant);

        return redirect()->route('tenant.network.mikrotik')
            ->with('success', "MikroTik Gateway '{$router->name}' updated successfully.");
    }

    /**
     * Toggle router enabled / disabled status.
     */
    public function toggleStatus($id)
    {
        $router = $this->findTenantRouter($id);
        $router->is_active = !$router->is_active;

        if (!$router->is_active) {
            $router->status = 'offline';
            $router->last_error = 'Router disabled by administrator.';
        } else {
            $router->last_error = null;
        }

        $router->save();

        $stateText = $router->is_active ? 'enabled' : 'disabled';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $router->is_active,
                'message' => "Router '{$router->name}' is now {$stateText}.",
            ]);
        }

        return back()->with('success', "MikroTik Gateway '{$router->name}' has been {$stateText} successfully.");
    }

    /**
     * Remove router.
     */
    public function destroy($id)
    {
        $tenant = $this->getTenant();
        $router = $this->findTenantRouter($id);
        $name = $router->name;
        $router->delete();

        // Synchronize FreeRADIUS NAS table
        $this->radiusService->syncNasClients($tenant);

        return redirect()->route('tenant.network.mikrotik')
            ->with('success', "MikroTik Gateway '{$name}' deleted successfully.");
    }

    /**
     * Test connection credentials via AJAX.
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string',
            'api_port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'router_id' => 'nullable|integer',
        ]);

        $password = $request->input('password');

        // If password wasn't provided, check if router_id is given to use saved password
        if (empty($password) && $request->filled('router_id')) {
            $router = $this->findTenantRouter($request->router_id);
            $password = $router->decrypted_password;
        }

        $result = $this->apiService->testConnection(
            $request->ip_address,
            (int) $request->api_port,
            $request->username,
            $password ?: '',
            $request->boolean('use_ssl'),
            4
        );

        return response()->json($result);
    }

    /**
     * Live sync router status and metrics into database.
     */
    public function sync($id)
    {
        $tenant = $this->getTenant();
        $router = $this->findTenantRouter($id);

        if (!$router->is_active) {
            return back()->with('error', "Cannot sync disabled router '{$router->name}'. Please enable it first.");
        }

        if ($router->connection_type === 'radius') {
            $this->radiusService->syncNasClients($tenant);
            $res = $this->apiService->syncRouter($router);
            if ($res['success']) {
                return back()->with('success', "RADIUS Gateway '{$router->name}' synchronized with FreeRADIUS NAS table & RouterOS API.");
            }
            return back()->with('warning', "FreeRADIUS NAS synchronized, but router is unreachable via API: {$res['message']}");
        }

        $result = $this->apiService->syncRouter($router);

        if ($result['success']) {
            return back()->with('success', "Live sync completed: {$result['message']}");
        }

        return back()->with('error', "Sync failed: {$result['message']}");
    }

    /**
     * Fetch interfaces via AJAX.
     */
    public function interfaces($id)
    {
        $router = $this->findTenantRouter($id);
        $res = $this->apiService->getInterfaces($router);

        return response()->json($res);
    }

    /**
     * Fetch active PPPoE sessions via AJAX.
     */
    public function sessions($id)
    {
        $router = $this->findTenantRouter($id);
        $res = $this->apiService->getActivePppoe($router);

        return response()->json($res);
    }

    /**
     * Reboot router safely.
     */
    public function reboot(Request $request, $id)
    {
        $router = $this->findTenantRouter($id);

        if (!$router->is_active) {
            $msg = "Cannot reboot disabled router '{$router->name}'.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $success = $this->apiService->reboot($router);

        if ($success) {
            $router->update(['status' => 'offline', 'last_error' => 'Reboot command initiated.']);
            $msg = "Reboot signal sent to router '{$router->name}'. System is restarting.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);
        }

        $err = 'Failed to send reboot signal: ' . ($this->apiService->getLastError() ?: 'Check router API connection and credentials');
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $err], 400);
        }
        return back()->with('error', $err);
    }

    /**
     * Real-time live telemetry stream for router deep-dive dashboard.
     */
    public function telemetry($id)
    {
        $router = $this->findTenantRouter($id);

        if (!$router->is_active) {
            return response()->json([
                'success' => false,
                'is_active' => false,
                'status' => 'disabled',
                'message' => 'Router gateway is disabled. Real-time telemetry is paused.',
                'latency_ms' => 0,
                'identity' => $router->name,
                'cpu_load' => null,
                'free_memory' => null,
                'total_memory' => null,
                'uptime' => $router->uptime,
                'model' => $router->model,
                'ros_version' => $router->ros_version,
                'last_sync_at' => 'Disabled',
                'last_sync_human' => 'Disabled',
                'last_error' => 'Router gateway disabled by administrator',
            ]);
        }

        $res = $this->apiService->syncRouter($router);

        return response()->json([
            'success' => $res['success'],
            'message' => $res['message'] ?? '',
            'latency_ms' => $res['latency_ms'] ?? 0,
            'identity' => $res['identity'] ?? $router->name,
            'status' => $router->status,
            'cpu_load' => $router->cpu_load,
            'free_memory' => $router->free_memory,
            'total_memory' => $router->total_memory,
            'uptime' => $router->uptime,
            'model' => $router->model,
            'ros_version' => $router->ros_version,
            'architecture' => $res['architecture'] ?? null,
            'cpu_count' => $res['cpu_count'] ?? 1,
            'cpu_frequency' => $res['cpu_frequency'] ?? null,
            'free_hdd' => $res['free_hdd'] ?? null,
            'total_hdd' => $res['total_hdd'] ?? null,
            'last_sync_at' => $router->last_sync_at ? $router->last_sync_at->format('H:i:s') : 'Just now',
            'last_sync_human' => $router->last_sync_at ? $router->last_sync_at->diffForHumans() : 'Just now',
            'last_error' => $router->last_error,
        ]);
    }
}
