<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Models\TenantRouter;
use App\Services\Network\OltApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class OltController extends Controller
{
    protected OltApiService $oltApiService;

    public function __construct(OltApiService $oltApiService)
    {
        $this->oltApiService = $oltApiService;
    }

    /**
     * OLT Gateway Fleet Directory
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404);
        }

        $query = TenantOlt::where('tenant_id', $tenant->id)
            ->with(['router'])
            ->withCount([
                'onus',
                'onus as online_onus_count' => function ($q) {
                    $q->where('status', 'online');
                },
                'onus as offline_onus_count' => function ($q) {
                    $q->where('status', '!=', 'online');
                },
            ]);

        // Search Keyword
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%")
                  ->orWhere('vendor', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%");
            });
        }

        // Router Filter
        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Vendor Filter
        if ($request->filled('vendor')) {
            $query->where(function ($q) use ($request) {
                $q->where('vendor', $request->vendor)
                  ->orWhere('model', 'like', "%{$request->vendor}%");
            });
        }

        // Connection Type Filter
        if ($request->filled('connection_type')) {
            $query->where('connection_type', $request->connection_type);
        }

        $perPage = (int)$request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        $olts = $query->latest('id')->paginate($perPage)->withQueryString();

        // Summary Metric Counters based on active search/vendor/protocol filters
        $metricBase = TenantOlt::where('tenant_id', $tenant->id);
        if ($request->filled('search')) {
            $s = trim($request->search);
            $metricBase->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%")
                  ->orWhere('vendor', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vendor')) {
            $metricBase->where(function ($q) use ($request) {
                $q->where('vendor', $request->vendor)
                  ->orWhere('model', 'like', "%{$request->vendor}%");
            });
        }
        if ($request->filled('connection_type')) {
            $metricBase->where('connection_type', $request->connection_type);
        }

        $totalOlts = (clone $metricBase)->count();
        $onlineOlts = (clone $metricBase)->where('status', 'online')->count();
        $offlineOlts = $totalOlts - $onlineOlts;

        $filteredOltIds = (clone $metricBase)->pluck('id');
        $totalOnus = TenantOnu::whereIn('olt_id', $filteredOltIds)->count();
        $onlineOnus = TenantOnu::whereIn('olt_id', $filteredOltIds)->where('status', 'online')->count();
        $offlineOnus = max(0, $totalOnus - $onlineOnus);
        $warningOnus = TenantOnu::whereIn('olt_id', $filteredOltIds)
            ->whereNotNull('rx_power_dbm')
            ->where('rx_power_dbm', '>=', -26.0)
            ->where('rx_power_dbm', '<', -20.0)
            ->count();
        $criticalOnus = TenantOnu::whereIn('olt_id', $filteredOltIds)
            ->whereNotNull('rx_power_dbm')
            ->where('rx_power_dbm', '<', -26.0)
            ->count();

        $routers = TenantRouter::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'ip_address', 'status']);

        // Plan Quota Resolution
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $oltQuota = $plan ? (int)($plan->olt_limit ?? 1) : 1;
        $quotaRemaining = max(0, $oltQuota - $totalOlts);
        $isQuotaReached = ($oltQuota > 0 && $totalOlts >= $oltQuota);

        return view('tenant.network.olt', compact(
            'tenant',
            'plan',
            'olts',
            'routers',
            'totalOlts',
            'onlineOlts',
            'offlineOlts',
            'totalOnus',
            'onlineOnus',
            'offlineOnus',
            'warningOnus',
            'criticalOnus',
            'oltQuota',
            'quotaRemaining',
            'isQuotaReached'
        ));
    }

    /**
     * Dedicated Create OLT Device Page
     */
    public function create()
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404);
        }

        $routers = TenantRouter::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'ip_address', 'status']);

        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $oltQuota = $plan ? (int)($plan->olt_limit ?? 1) : 1;
        $totalOlts = TenantOlt::where('tenant_id', $tenant->id)->count();
        $quotaRemaining = max(0, $oltQuota - $totalOlts);
        $isQuotaReached = ($oltQuota > 0 && $totalOlts >= $oltQuota);

        return view('tenant.network.olt_create', compact('tenant', 'plan', 'routers', 'oltQuota', 'totalOlts', 'quotaRemaining', 'isQuotaReached'));
    }

    /**
     * Dedicated Edit OLT Device Page
     */
    public function edit($id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404);
        }

        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);
        $routers = TenantRouter::where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name', 'ip_address', 'status']);

        return view('tenant.network.olt_edit', compact('tenant', 'olt', 'routers'));
    }

    /**
     * Store newly created OLT Gateway
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404);
        }

        // Enforce ISP Tenant Plan OLT Quota
        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        $oltQuota = $plan ? (int)($plan->olt_limit ?? 1) : 1;
        $currentCount = TenantOlt::where('tenant_id', $tenant->id)->count();

        if ($oltQuota > 0 && $currentCount >= $oltQuota) {
            $planName = $plan ? $plan->name : 'Current Plan';
            $msg = "You have reached your Plan ({$planName}) quota limit of {$oltQuota} OLT devices. Please upgrade your subscription plan to add more OLTs.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 403);
            }
            return back()->withErrors(['plan' => $msg])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'required|string|max:100',
            'web_port' => 'nullable|integer|between:1,65535',
            'web_username' => 'nullable|string|max:80',
            'web_password' => 'nullable|string|max:255',
            'connection_type' => 'nullable|string|in:web_api,snmp,telnet,hybrid',
            'vendor' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'total_pon_ports' => 'nullable|integer|between:1,64',
            'snmp_port' => 'nullable|integer|between:1,65535',
            'snmp_community' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $olt = new TenantOlt();
        $olt->tenant_id = $tenant->id;
        $olt->router_id = !empty($validated['router_id']) ? $validated['router_id'] : null;
        $olt->name = $validated['name'];
        $olt->ip_address = $validated['ip_address'];
        $olt->web_port = $validated['web_port'] ?? 80;
        $olt->web_username = $validated['web_username'] ?? 'root';
        if (!empty($validated['web_password'])) {
            $olt->web_password = $validated['web_password'];
        }
        $olt->connection_type = $validated['connection_type'] ?? 'web_api';
        $olt->vendor = $validated['vendor'] ?? 'EPON OLT';
        $olt->model = $validated['model'] ?? 'EPON OLT';
        $olt->total_pon_ports = $validated['total_pon_ports'] ?? 4;
        $olt->snmp_port = $validated['snmp_port'] ?? 161;
        $olt->snmp_community = $validated['snmp_community'] ?? 'public';
        $olt->notes = $validated['notes'] ?? null;
        $olt->status = 'offline';
        $olt->save();

        // Trigger initial background sync
        try {
            $this->oltApiService->syncOlt($olt);
        } catch (\Exception $e) {
            Log::warning("Initial OLT sync failed for #{$olt->id}: " . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'OLT device created and connected successfully.',
                'olt' => $olt,
                'redirect' => route('tenant.network.olt')
            ]);
        }

        return redirect()->route('tenant.network.olt')->with('success', 'OLT device created and connected successfully.');
    }

    /**
     * Show OLT Gateway Live Chassis & Telemetry
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404);
        }

        $olt = TenantOlt::where('tenant_id', $tenant->id)
            ->withCount([
                'onus',
                'onus as online_onus_count' => fn($q) => $q->where('status', 'online'),
                'onus as offline_onus_count' => fn($q) => $q->where('status', '!=', 'online'),
            ])
            ->findOrFail($id);

        $selectedPon = $request->query('pon', 'all');
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search');

        // Grouped PON breakdown
        $ponStats = TenantOnu::where('olt_id', $olt->id)
            ->selectRaw('pon_port, count(*) as total, sum(case when status=\'online\' then 1 else 0 end) as online_count')
            ->groupBy('pon_port')
            ->orderBy('pon_port')
            ->get();

        $totalPorts = (int) ($olt->total_pon_ports ?: ($olt->pon_ports_count ?: max($ponStats->count(), 4)));
        if ($totalPorts < 1) {
            $totalPorts = 4;
        }

        // Build Real Sequential PON Ports in a single line (PON 01 to PON N based on real hardware)
        $ponList = [];
        $discoveredPorts = $ponStats->values();

        for ($i = 1; $i <= $totalPorts; $i++) {
            $padNum = str_pad($i, 2, '0', STR_PAD_LEFT);
            $portTitle = "PON {$padNum}";
            
            $stat = $ponStats->first(function($s) use ($i) {
                return $s->pon_port === "0/{$i}" 
                    || $s->pon_port === "EPON0/{$i}"
                    || $s->pon_port === "GPON0/{$i}"
                    || $s->pon_port === (string)$i 
                    || str_ends_with($s->pon_port, "/{$i}");
            });
            
            if ($stat) {
                $fullPort = $stat->pon_port;
                $total = (int) $stat->total;
                $online = (int) $stat->online_count;
                $offline = max(0, $total - $online);
                $hasOnus = ($total > 0);
                $isOnline = ($online > 0);
            } else {
                $fullPort = "0/{$i}";
                $total = 0;
                $online = 0;
                $offline = 0;
                $hasOnus = false;
                $isOnline = false;
            }

            $ponList[] = [
                'index' => $i,
                'title' => $portTitle,
                'full_port' => $fullPort,
                'total' => $total,
                'online' => $online,
                'offline' => $offline,
                'is_online' => $isOnline,
                'has_onus' => $hasOnus,
            ];
        }

        $totalOnus = $olt->onus_count;
        $onlineOnus = $olt->online_onus_count;
        $offlineOnus = $olt->offline_onus_count;
        $onlinePortsCount = collect($ponList)->where('is_online', true)->count();

        return view('tenant.network.olt_show', compact(
            'tenant',
            'olt',
            'ponStats',
            'ponList',
            'totalPorts',
            'onlinePortsCount',
            'totalOnus',
            'onlineOnus',
            'offlineOnus'
        ));
    }

    /**
     * Update existing OLT Gateway
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_address' => 'required|string|max:100',
            'web_port' => 'nullable|integer|between:1,65535',
            'web_username' => 'nullable|string|max:80',
            'web_password' => 'nullable|string|max:255',
            'connection_type' => 'nullable|string|in:web_api,snmp,telnet,hybrid',
            'vendor' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'total_pon_ports' => 'nullable|integer|between:1,64',
            'snmp_port' => 'nullable|integer|between:1,65535',
            'snmp_community' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $olt->name = $validated['name'];
        $olt->router_id = !empty($validated['router_id']) ? $validated['router_id'] : null;
        $olt->ip_address = $validated['ip_address'];
        $olt->web_port = $validated['web_port'] ?? 80;
        $olt->web_username = $validated['web_username'] ?? 'root';
        if (!empty($validated['web_password'])) {
            $olt->web_password = $validated['web_password'];
        }
        $olt->connection_type = $validated['connection_type'] ?? 'web_api';
        $olt->vendor = $validated['vendor'] ?? $olt->vendor;
        $olt->model = $validated['model'] ?? $olt->model;
        $olt->total_pon_ports = $validated['total_pon_ports'] ?? $olt->total_pon_ports;
        $olt->snmp_port = $validated['snmp_port'] ?? 161;
        $olt->snmp_community = $validated['snmp_community'] ?? 'public';
        $olt->notes = $validated['notes'] ?? $olt->notes;
        $olt->save();

        // Trigger background sync on update
        try {
            $this->oltApiService->syncOlt($olt);
        } catch (\Exception $e) {
            Log::warning("OLT update sync error: " . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'OLT device updated successfully.',
                'olt' => $olt,
                'redirect' => route('tenant.network.olt')
            ]);
        }

        return redirect()->route('tenant.network.olt')->with('success', 'OLT device updated successfully.');
    }

    /**
     * Delete an OLT Gateway & associated ONUs
     */
    public function destroy(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        TenantOnu::where('olt_id', $olt->id)->delete();
        $olt->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'OLT device and client inventory removed successfully.',
                'redirect' => route('tenant.network.olt')
            ]);
        }

        return redirect()->route('tenant.network.olt')->with('success', 'OLT device removed successfully.');
    }

    /**
     * Live Test Connection probe
     */
    public function testConnection(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        $oltId = $request->input('olt_id');

        if ($oltId) {
            $olt = TenantOlt::where('tenant_id', $tenant->id)->find($oltId);
            if (!$olt) {
                return response()->json(['success' => false, 'message' => 'OLT device not found.'], 404);
            }
            $ip = $olt->ip_address;
            $port = (int) ($olt->web_port ?: 80);
            $username = $olt->web_username ?: 'root';
            $password = $olt->decrypted_web_password ?: 'admin';
        } else {
            $validated = $request->validate([
                'ip_address' => 'required|string',
                'web_port' => 'nullable|integer|between:1,65535',
                'web_username' => 'nullable|string',
                'web_password' => 'nullable|string',
            ]);
            $ip = $validated['ip_address'];
            $port = (int) ($validated['web_port'] ?? 80);
            $username = $validated['web_username'] ?? 'root';
            $password = $validated['web_password'] ?? 'admin';
        }

        $result = $this->oltApiService->testConnection($ip, $port, $username, $password);
        return response()->json($result);
    }

    /**
     * Synchronize live OLT hardware data & all ONUs to database
     */
    public function sync(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $res = $this->oltApiService->syncOlt($olt);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message']);
    }

    /**
     * Remote Reboot an ONU via OLT
     */
    public function rebootOnu(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);
        $validated = $request->validate([
            'pon_port' => 'required|string',
            'onu_id' => 'required|integer|min:1',
        ]);

        $success = $this->oltApiService->rebootOnu($olt, $validated['pon_port'], (int)$validated['onu_id']);
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "ONU {$validated['pon_port']}:{$validated['onu_id']} reboot command sent successfully.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to reboot ONU. Check OLT connectivity.",
        ], 500);
    }

    /**
     * Fetch newly discovered unregistered ONUs (Auto-find)
     */
    public function autofind(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $list = $this->oltApiService->getAutoFindOnus(
            $olt->ip_address,
            $olt->web_port ?: 80,
            $olt->web_username ?: 'root',
            $olt->decrypted_web_password ?: 'admin'
        );

        return response()->json([
            'success' => true,
            'count' => count($list),
            'autofind_onus' => $list,
        ]);
    }

    /**
     * Live Telemetry JSON for frontend polling
     */
    public function telemetry(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $deviceStatus = $this->oltApiService->getDeviceStatus(
            $olt->ip_address,
            $olt->web_port ?: 80,
            $olt->web_username ?: 'root',
            $olt->decrypted_web_password ?: 'admin'
        );
        $alarms = $this->oltApiService->getActiveAlarms(
            $olt->ip_address,
            $olt->web_port ?: 80,
            $olt->web_username ?: 'root',
            $olt->decrypted_web_password ?: 'admin'
        );

        $rawUp = (int) ($deviceStatus['UpTime'] ?? 0);
        $uptimeSeconds = $rawUp > 10000000 ? (int) floor($rawUp / 1000) : $rawUp;

        return response()->json([
            'success' => true,
            'status' => $olt->status,
            'uptime' => $olt->uptime,
            'uptime_seconds' => $uptimeSeconds,
            'active_alarms_count' => count($alarms),
            'device_status' => $deviceStatus,
            'last_sync' => $olt->last_sync_at?->diffForHumans() ?? 'Never',
        ]);
    }

    /**
     * Real-time Port Statistics and Physical Port Status for OLT
     */
    public function portStats(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $ports = $this->oltApiService->getPortInfo(
            $olt->ip_address,
            $olt->web_port ?: 80,
            $olt->web_username ?: 'root',
            $olt->decrypted_web_password ?: 'admin'
        );

        $portIndex = $request->input('port_index');
        if (!$portIndex) {
            if (!empty($ports['xge'])) {
                foreach ($ports['xge'] as $x) {
                    if (($x['Status'] ?? 0) === 2) {
                        $portIndex = $x['Index'];
                        break;
                    }
                }
            }
            if (!$portIndex && !empty($ports['gpon'][0]['Index'])) {
                $portIndex = $ports['gpon'][0]['Index'];
            }
            if (!$portIndex && !empty($ports['geCopper'][0]['Index'])) {
                $portIndex = $ports['geCopper'][0]['Index'];
            }
        }

        $stats = [];
        if ($portIndex) {
            $stats = $this->oltApiService->getPortStatistics(
                $olt->ip_address,
                $olt->web_port ?: 80,
                $olt->web_username ?: 'root',
                $olt->decrypted_web_password ?: 'admin',
                $portIndex
            );
        }

        return response()->json([
            'success' => true,
            'olt_id' => $olt->id,
            'olt_name' => $olt->name,
            'ports' => $ports,
            'selected_index' => (int) $portIndex,
            'stats' => $stats,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Clear / Reset Port Statistics Counters on OLT
     */
    public function resetPortStats(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        $olt = TenantOlt::where('tenant_id', $tenant->id)->findOrFail($id);

        $portIndex = $request->input('port_index');
        if (!$portIndex) {
            return response()->json(['success' => false, 'message' => 'Port index is required.'], 422);
        }

        $ok = $this->oltApiService->resetPortStatistics(
            $olt->ip_address,
            $olt->web_port ?: 80,
            $olt->web_username ?: 'root',
            $olt->decrypted_web_password ?: 'admin',
            $portIndex
        );

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Port statistics cleared successfully!' : 'Failed to clear port statistics.'
        ]);
    }
}
