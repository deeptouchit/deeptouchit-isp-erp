<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
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
            abort(404, 'ISP Tenant record not found.');
        }

        $olts = TenantOlt::where('tenant_id', $tenant->id)->get();

        $query = TenantOnu::where('tenant_id', $tenant->id)->with('olt');

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

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('onu_id', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%");
            });
        }

        $perPage = (int)$request->input('per_page', 30);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 30;
        }

        $onus = $query->latest('id')->paginate($perPage)->withQueryString();

        // Calculate summary metric counters based on active filter scope
        $metricBase = TenantOnu::where('tenant_id', $tenant->id);

        if ($oltId = $request->input('olt_id')) {
            $metricBase->where('olt_id', $oltId);
        }

        if ($onuType = $request->input('onu_type')) {
            $metricBase->where('onu_type', $onuType);
        }

        if ($search = $request->input('search')) {
            $metricBase->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('onu_id', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('desc', 'like', "%{$search}%");
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

        return view('tenant.network.onu', compact(
            'tenant',
            'olts',
            'onus',
            'totalOnus',
            'onlineOnus',
            'offlineOnus',
            'criticalOnus',
            'warningOnus'
        ));
    }

    /**
     * Get single ONU details (JSON)
     */
    public function show(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $onu->append('signal_quality');

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
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'desc' => 'nullable|string|max:500',
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

        $onu->load('olt');
        $onu->append('signal_quality');

        return response()->json([
            'success' => true,
            'message' => 'ONU configuration saved successfully' . ($oltResult['pushed_to_olt'] ? ' and synchronized with OLT.' : '.'),
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
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'Associated OLT not found.',
            ], 404);
        }

        $success = $this->oltApiService->rebootOnu($olt, $onu->pon_port, (int)$onu->onu_id);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "ONU {$onu->pon_port}:#{$onu->onu_id} ({$onu->name}) reboot signal sent successfully.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to send reboot signal to ONU. Check OLT communication.",
        ], 500);
    }

    /**
     * Restore ONU to Factory Defaults
     */
    public function factoryReset(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'Associated OLT not found.',
            ], 404);
        }

        $this->oltApiService->factoryResetOnu($olt, $onu->pon_port, (int)$onu->onu_id);

        return response()->json([
            'success' => true,
            'message' => "Factory default reset command sent to ONU {$onu->pon_port}:#{$onu->onu_id}.",
        ]);
    }

    /**
     * Fetch Live Optical Telemetry for an ONU in Real Time
     */
    public function optical(Request $request, $id)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $onu = TenantOnu::where('tenant_id', $tenant->id)->with('olt')->findOrFail($id);
        $olt = $onu->olt;

        if (!$olt) {
            return response()->json([
                'success' => false,
                'message' => 'Associated OLT not found.',
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
            'message' => $telemetry ? 'Live optical telemetry fetched successfully.' : 'Optical telemetry updated from latest cache.',
            'telemetry' => $telemetry,
            'onu' => $onu,
        ]);
    }
}
