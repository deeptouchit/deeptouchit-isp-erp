<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantCustomer;
use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Services\Network\OltApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OnuController extends Controller
{
    public function __construct(
        protected OltApiService $oltApiService
    ) {}

    /**
     * Display a listing of ONU / ONT Terminals across all OLTs.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, __('ISP Tenant record not found.'));
        }

        $olts = TenantOlt::where('tenant_id', $tenant->id)->get();

        $query = TenantOnu::where('tenant_id', $tenant->id)->with(['olt', 'customer.coverageZone']);

        if ($oltId = $request->input('olt_id')) {
            $query->where('olt_id', $oltId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'online') {
                $query->where('status', 'online');
            } elseif ($status === 'offline') {
                $query->where('status', 'offline');
            } elseif ($status === 'good') {
                $query->where('rx_power_dbm', '>=', -20);
            } elseif ($status === 'warning') {
                $query->where('rx_power_dbm', '>=', -26)->where('rx_power_dbm', '<', -20);
            } elseif ($status === 'critical') {
                $query->where('rx_power_dbm', '<', -26);
            }
        }

        if ($onuType = $request->input('onu_type')) {
            $query->where('onu_type', $onuType);
        }

        if ($ponPort = $request->input('pon_port')) {
            $query->where('pon_port', $ponPort);
        }

        if ($vendor = $request->input('vendor')) {
            $query->where('vendor', $vendor);
        }

        if ($binding = $request->input('binding')) {
            if ($binding === 'linked') {
                $query->where(function ($q) {
                    $q->whereNotNull('customer_id')->orWhereNotNull('router_mac');
                });
            } elseif ($binding === 'unlinked') {
                $query->whereNull('customer_id')->whereNull('router_mac');
            }
        }

        if ($distRange = $request->input('distance_range')) {
            if ($distRange === 'under_500') {
                $query->where('distance_m', '<', 500);
            } elseif ($distRange === '500_1000') {
                $query->whereBetween('distance_m', [500, 1000]);
            } elseif ($distRange === '1000_2000') {
                $query->whereBetween('distance_m', [1000, 2000]);
            } elseif ($distRange === 'over_2000') {
                $query->where('distance_m', '>', 2000);
            }
        }

        $matchedUsernames = [];
        if ($search = $request->input('search')) {
            $matchedUsernames = TenantCustomer::where('tenant_id', $tenant->id)
                ->where(function ($cq) use ($search) {
                    $cq->where('mac_address', 'like', "%{$search}%")
                       ->orWhere('name', 'like', "%{$search}%");
                })
                ->pluck('username')
                ->filter()
                ->toArray();

            $query->where(function ($q) use ($search, $matchedUsernames) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('router_mac', 'like', "%{$search}%")
                  ->orWhere('onu_id', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%")
                  ->orWhere('pppoe_username', 'like', "%{$search}%");

                if (!empty($matchedUsernames)) {
                    $q->orWhereIn('pppoe_username', $matchedUsernames);
                }
            });
        }

        $perPage = (int)$request->input('per_page', 30);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 30;
        }

        $onus = $query->latest('id')->paginate($perPage)->withQueryString();

        // High-performance batch pre-resolving of customer router MACs & Client names
        $usernames = $onus->pluck('pppoe_username')->filter()->unique()->toArray();
        $onuMacs = $onus->pluck('mac_address')->filter()->unique()->toArray();
        $routerMacs = $onus->pluck('router_mac')->filter()->unique()->toArray();

        $customersByUsername = !empty($usernames) ? TenantCustomer::where('tenant_id', $tenant->id)
            ->with('coverageZone')
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy(fn($c) => strtolower(trim((string)$c->username))) : collect();

        $customersByOnuMac = !empty($onuMacs) ? TenantCustomer::where('tenant_id', $tenant->id)
            ->with('coverageZone')
            ->whereIn('onu_mac_sn', $onuMacs)
            ->get()
            ->keyBy(fn($c) => strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$c->onu_mac_sn))) : collect();

        $customersByRouterMac = !empty($routerMacs) ? TenantCustomer::where('tenant_id', $tenant->id)
            ->with('coverageZone')
            ->whereIn('mac_address', $routerMacs)
            ->get()
            ->keyBy(fn($c) => strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$c->mac_address))) : collect();

        foreach ($onus as $onu) {
            $cleanOnuMac = !empty($onu->mac_address) ? strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$onu->mac_address)) : null;
            $cleanRMac = !empty($onu->router_mac) ? strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$onu->router_mac)) : null;
            $uKey = !empty($onu->pppoe_username) ? strtolower(trim((string)$onu->pppoe_username)) : null;

            $matchedCust = $onu->customer
                ?: ($uKey ? $customersByUsername->get($uKey) : null)
                ?: ($cleanRMac ? $customersByRouterMac->get($cleanRMac) : null)
                ?: ($cleanOnuMac ? $customersByOnuMac->get($cleanOnuMac) : null);

            if ($matchedCust) {
                if (!$onu->relationLoaded('customer') || !$onu->customer) {
                    $onu->setRelation('customer', $matchedCust);
                }
                if (empty($onu->customer_id)) {
                    $onu->linked_customer_id = $matchedCust->id;
                }
                if (empty($onu->router_mac) && !empty($matchedCust->mac_address)) {
                    $onu->auto_router_mac = $matchedCust->mac_address;
                }
                if (empty($onu->pppoe_username) && !empty($matchedCust->username)) {
                    $onu->pppoe_username = $matchedCust->username;
                }
                $onu->resolved_client_name = $matchedCust->name;
                $onu->resolved_area = $matchedCust->zone ?: ($matchedCust->coverageZone?->name ?? null);
            }
        }

        // Calculate summary metric counters based on active filter scope
        $metricBase = TenantOnu::where('tenant_id', $tenant->id);

        if ($oltId = $request->input('olt_id')) {
            $metricBase->where('olt_id', $oltId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'online') {
                $metricBase->where('status', 'online');
            } elseif ($status === 'offline') {
                $metricBase->where('status', 'offline');
            } elseif ($status === 'good') {
                $metricBase->where('rx_power_dbm', '>=', -20);
            } elseif ($status === 'warning') {
                $metricBase->where('rx_power_dbm', '>=', -26)->where('rx_power_dbm', '<', -20);
            } elseif ($status === 'critical') {
                $metricBase->where('rx_power_dbm', '<', -26);
            }
        }

        if ($onuType = $request->input('onu_type')) {
            $metricBase->where('onu_type', $onuType);
        }

        if ($ponPort = $request->input('pon_port')) {
            $metricBase->where('pon_port', $ponPort);
        }

        if ($vendor = $request->input('vendor')) {
            $metricBase->where('vendor', $vendor);
        }

        if ($binding = $request->input('binding')) {
            if ($binding === 'linked') {
                $metricBase->where(function ($q) {
                    $q->whereNotNull('customer_id')->orWhereNotNull('router_mac');
                });
            } elseif ($binding === 'unlinked') {
                $metricBase->whereNull('customer_id')->whereNull('router_mac');
            }
        }

        if ($distRange = $request->input('distance_range')) {
            if ($distRange === 'under_500') {
                $metricBase->where('distance_m', '<', 500);
            } elseif ($distRange === '500_1000') {
                $metricBase->whereBetween('distance_m', [500, 1000]);
            } elseif ($distRange === '1000_2000') {
                $metricBase->whereBetween('distance_m', [1000, 2000]);
            } elseif ($distRange === 'over_2000') {
                $metricBase->where('distance_m', '>', 2000);
            }
        }

        if ($search = $request->input('search')) {
            $metricBase->where(function ($q) use ($search, $matchedUsernames) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('router_mac', 'like', "%{$search}%")
                  ->orWhere('onu_id', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%")
                  ->orWhere('pppoe_username', 'like', "%{$search}%");

                if (!empty($matchedUsernames)) {
                    $q->orWhereIn('pppoe_username', $matchedUsernames);
                }
            });
        }

        $totalOnus = (clone $metricBase)->count();
        $onlineOnus = (clone $metricBase)->where('status', 'online')->count();
        $offlineOnus = max(0, $totalOnus - $onlineOnus);
        $criticalOnus = (clone $metricBase)->where('rx_power_dbm', '<', -26)->count();
        $warningOnus = (clone $metricBase)
            ->where('rx_power_dbm', '>=', -26)
            ->where('rx_power_dbm', '<', -20)
            ->count();

        $distinctPonPorts = TenantOnu::where('tenant_id', $tenant->id)
            ->when($oltId, fn($q) => $q->where('olt_id', $oltId))
            ->distinct()
            ->orderBy('pon_port')
            ->pluck('pon_port')
            ->filter()
            ->values();

        $distinctVendors = TenantOnu::where('tenant_id', $tenant->id)
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor')
            ->filter()
            ->values();

        return view('tenant.network.onu', compact(
            'tenant',
            'olts',
            'onus',
            'totalOnus',
            'onlineOnus',
            'offlineOnus',
            'criticalOnus',
            'warningOnus',
            'distinctPonPorts',
            'distinctVendors'
        ));
    }

    /**
     * Synchronize all active OLT hardware devices and auto-link customer bindings.
     */
    public function syncAll(Request $request)
    {
        @set_time_limit(300);
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $olts = TenantOlt::where('tenant_id', $tenant->id)->get();
        if ($olts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('No active OLT hardware gateways configured. Please register an OLT device first.'),
            ], 422);
        }

        $syncedCount = 0;
        $failedCount = 0;
        $details = [];

        foreach ($olts as $olt) {
            try {
                $res = $this->oltApiService->syncOlt($olt);
                if (!empty($res['success'])) {
                    $syncedCount++;
                    $details[] = "{$olt->name}: " . ($res['message'] ?? __('Synced'));
                } else {
                    $failedCount++;
                    $details[] = "{$olt->name}: " . ($res['message'] ?? __('Connection error'));
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $details[] = "{$olt->name}: " . $e->getMessage();
                Log::error("Manual OLT sync exception for {$olt->name}: " . $e->getMessage());
            }
        }

        // Trigger auto-link
        try {
            \Illuminate\Support\Facades\Artisan::call('isp:auto-link-onus', [
                '--tenant' => $tenant->id,
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Auto-link error during syncAll: " . $e->getMessage());
        }

        $totalOnus = TenantOnu::where('tenant_id', $tenant->id)->count();
        $onlineOnus = TenantOnu::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $linkedCount = TenantOnu::where('tenant_id', $tenant->id)
            ->where(function ($q) {
                $q->whereNotNull('customer_id')->orWhereNotNull('router_mac');
            })->count();

        $msg = __('Synchronized :synced of :total OLT(s). Total :onus ONUs (:online online, :linked linked with clients).', [
            'synced' => $syncedCount,
            'total' => $olts->count(),
            'onus' => $totalOnus,
            'online' => $onlineOnus,
            'linked' => $linkedCount,
        ]);

        return response()->json([
            'success' => $syncedCount > 0,
            'message' => $msg,
            'details' => $details,
            'total_onus' => $totalOnus,
            'online_onus' => $onlineOnus,
            'linked_onus' => $linkedCount,
        ]);
    }

    /**
     * Re-evaluate and link customer profiles with ONUs via Router MAC & Usernames.
     */
    public function autoLink(Request $request)
    {
        @set_time_limit(180);
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('isp:auto-link-onus', [
                '--tenant' => $tenant->id,
                '--force' => true,
            ]);

            $linkedCount = TenantOnu::where('tenant_id', $tenant->id)
                ->where(function ($q) {
                    $q->whereNotNull('customer_id')->orWhereNotNull('router_mac');
                })->count();

            return response()->json([
                'success' => true,
                'message' => __('Client auto-linking completed successfully. :count ONUs are now linked with customer profiles.', [
                    'count' => $linkedCount,
                ]),
                'linked_onus' => $linkedCount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to auto-link customers: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Get single ONU details (JSON)
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with(['olt', 'customer'])->findOrFail($id);
        $onu->append(['signal_quality', 'display_router_mac']);

        return response()->json([
            'success' => true,
            'onu' => $onu,
        ]);
    }

    /**
     * Update & Configure ONU (Client info, VLAN, Port, PPPoE/WiFi settings)
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'desc' => 'nullable|string|max:500',
            'router_mac' => 'nullable|string|max:50',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'vlan_mode' => 'nullable|string|in:tag,untag,transparent,hybrid',
            'service_mode' => 'nullable|string|in:bridge,pppoe,dhcp,static',
            'pppoe_username' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'lan1_state' => 'nullable|string|in:enable,disable',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'catv_state' => 'nullable|string|in:enable,disable',
            'bandwidth_profile' => 'nullable|string|max:100',
        ]);

        $onu->update($validated);

        // Push configuration changes to the OLT device
        $oltResult = ['pushed_to_olt' => false];
        if ($onu->olt) {
            $oltResult = $this->oltApiService->configureOnu($onu->olt, $onu, $validated);
        }

        $onu->load(['olt', 'customer']);
        $onu->append(['signal_quality', 'display_router_mac']);

        return response()->json([
            'success' => true,
            'message' => $oltResult['pushed_to_olt']
                ? __('ONU configuration saved successfully and synchronized with OLT.')
                : __('ONU configuration saved successfully.'),
            'onu' => $onu,
        ]);
    }

    /**
     * Remote Reboot an ONU via OLT API
     */
    public function reboot(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $success = $this->oltApiService->rebootOnu($olt, $onu->pon_port, (int)$onu->onu_id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => __('ONU :port:#:id (:name) reboot signal sent successfully.', [
                    'port' => $onu->pon_port,
                    'id' => $onu->onu_id,
                    'name' => $onu->name,
                ]),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Failed to send reboot signal to ONU. Check OLT communication.'),
        ], 500);
    }

    /**
     * Restore ONU to Factory Defaults
     */
    public function factoryReset(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $this->oltApiService->factoryResetOnu($olt, $onu->pon_port, (int)$onu->onu_id);

        return response()->json([
            'success' => true,
            'message' => __('Factory default reset command sent to ONU :port:#:id.', [
                'port' => $onu->pon_port,
                'id' => $onu->onu_id,
            ]),
        ]);
    }

    /**
     * Fetch Live Optical Telemetry for an ONU in Real Time
     */
    public function optical(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $telemetry = $this->oltApiService->getOnuOpticalTelemetry($olt, $onu->pon_port, (int)$onu->onu_id);

        if ($telemetry) {
            $onu->update([
                'rx_power_dbm' => $telemetry['rx_power_dbm'] ?? $onu->rx_power_dbm,
                'tx_power_dbm' => $telemetry['tx_power_dbm'] ?? $onu->tx_power_dbm,
                'distance_m' => $telemetry['distance_m'] ?? $onu->distance_m,
            ]);
        }

        $onu->load('olt');
        $onu->append('signal_quality');

        return response()->json([
            'success' => true,
            'message' => $telemetry
                ? __('Live optical telemetry fetched successfully.')
                : __('Optical telemetry updated from latest cache.'),
            'telemetry' => $telemetry,
            'onu' => $onu,
        ]);
    }
}
