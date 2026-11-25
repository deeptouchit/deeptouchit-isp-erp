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
        $request->validate([
            'search' => 'nullable|string|max:200', 'olt_id' => 'nullable|integer|min:1',
            'status' => 'nullable|in:online,offline,good,warning,critical,unknown',
            'onu_type' => 'nullable|string|max:30', 'pon_port' => 'nullable|string|max:20',
            'vendor' => 'nullable|string|max:100', 'binding' => 'nullable|in:linked,unlinked',
            'distance_range' => 'nullable|in:under_500,500_1000,1000_2000,over_2000',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, __('ISP Tenant record not found.'));
        }

        $olts = TenantOlt::where('tenant_id', $tenant->id)->get();
        $onuCapabilities = $olts->mapWithKeys(fn ($olt) => [$olt->id => $this->oltApiService->onuCapabilities($olt)]);

        $query = TenantOnu::where('tenant_id', $tenant->id)->with([
            'olt' => fn ($q) => $q->where('tenant_id', $tenant->id),
            'customer' => fn ($q) => $q->where('tenant_id', $tenant->id)->with('coverageZone'),
        ]);

        if ($oltId = $request->input('olt_id')) {
            $query->where('olt_id', $oltId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'online') {
                $query->where('status', 'online');
            } elseif ($status === 'offline') {
                $query->where('status', 'offline');
            } elseif ($status === 'unknown') {
                $query->where('status', 'unknown');
            } elseif ($status === 'good') {
                $query->where('rx_power_dbm', '!=', 0)->where('rx_power_dbm', '>=', config('onu.signal.good_min_dbm'));
            } elseif ($status === 'warning') {
                $query->where('rx_power_dbm', '>=', config('onu.signal.warning_min_dbm'))->where('rx_power_dbm', '<', config('onu.signal.good_min_dbm'));
            } elseif ($status === 'critical') {
                $query->where('rx_power_dbm', '<', config('onu.signal.warning_min_dbm'));
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
                    $q->whereNotNull('customer_id');
                });
            } elseif ($binding === 'unlinked') {
                $query->whereNull('customer_id');
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

        $metricBase = clone $query;
        $onus = $query->latest('id')->paginate($perPage)->withQueryString();

        foreach ($onus as $onu) {
            $onu->resolved_client_name = $onu->customer?->name;
            $onu->resolved_area = $onu->customer?->coverageZone?->name;
        }

        // Reuse exactly the same filters for the table and counters.
        $totalOnus = (clone $metricBase)->count();
        $onlineOnus = (clone $metricBase)->where('status', 'online')->count();
        $offlineOnus = (clone $metricBase)->whereIn('status', ['offline', 'los', 'unregistered'])->count();
        $criticalOnus = (clone $metricBase)->where('rx_power_dbm', '<', config('onu.signal.warning_min_dbm'))->count();
        $warningOnus = (clone $metricBase)
            ->where('rx_power_dbm', '>=', config('onu.signal.warning_min_dbm'))
            ->where('rx_power_dbm', '<', config('onu.signal.good_min_dbm'))
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
            'onuCapabilities',
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
        return $this->queueOperation(false);
    }

    /**
     * Re-evaluate and link customer profiles with ONUs via Router MAC & Usernames.
     */
    public function autoLink(Request $request)
    {
        return $this->queueOperation(true);
    }

    private function queueOperation(bool $linkOnly)
    {
        $tenant = Auth::user()->tenant;
        if (!$linkOnly && !TenantOlt::where('tenant_id', $tenant->id)->exists()) {
            return response()->json(['success' => false, 'message' => __('No OLT devices configured.')], 422);
        }
        $pendingKey = 'onu-operation-pending:' . $tenant->id;
        $operation = (string) \Illuminate\Support\Str::uuid();
        if (!\Illuminate\Support\Facades\Cache::add($pendingKey, $operation, now()->addMinutes(15))) {
            return response()->json(['success' => false, 'message' => __('An ONU operation is already queued or running.')], 409);
        }
        try {
            \Illuminate\Support\Facades\Cache::put('onu-operation-status:' . $tenant->id . ':' . $operation,
                ['state' => 'queued', 'success' => true], now()->addHour());
            \App\Jobs\SyncTenantOnus::dispatch($tenant->id, $operation, $linkOnly);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Cache::forget($pendingKey);
            Log::error('Unable to queue ONU operation', ['tenant_id' => $tenant->id]);
            return response()->json(['success' => false, 'message' => __('ONU operation failed. Please try again.')], 503);
        }
        return response()->json(['success' => true, 'state' => 'queued',
            'status_url' => route('tenant.network.onu.operation-status', $operation),
            'message' => __('ONU operation queued.')], 202);
    }

    public function operationStatus(Request $request, string $operation)
    {
        abort_unless(\Illuminate\Support\Str::isUuid($operation), 404);
        $status = \Illuminate\Support\Facades\Cache::get('onu-operation-status:' . Auth::user()->tenant_id . ':' . $operation);
        abort_unless($status, 404);
        return response()->json($status);
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
        $onu->unsetRelations();
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
            'router_mac' => ['nullable', 'string', 'max:50', 'regex:/^(?:[a-fA-F0-9]{2}[:-]){5}[a-fA-F0-9]{2}$|^[a-fA-F0-9]{12}$|^(?:[a-fA-F0-9]{4}\.){2}[a-fA-F0-9]{4}$/'],
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

        // Push configuration changes to the OLT device
        $oltResult = ['success' => false, 'pushed_to_olt' => false];
        $validated = array_filter($validated, fn ($value, $key) => $onu->getAttribute($key) != $value, ARRAY_FILTER_USE_BOTH);
        if ($onu->olt && $onu->olt->tenant_id == $tenant->id) {
            $oltResult = $this->oltApiService->configureOnu($onu->olt, $onu, $validated);
        }
        $this->audit($request, $onu, 'ONU_CONFIGURE', (bool) $oltResult['success']);
        if (!$oltResult['success']) {
            return response()->json(['success' => false, 'message' => __('Device changes were not confirmed. Refresh device data before trying again.')], 422);
        }
        $saved = $validated;
        foreach (['name', 'desc'] as $field) {
            if (array_key_exists($field, $saved)) {
                $saved['local_' . $field] = $saved[$field];
                unset($saved[$field]);
            }
        }
        $onu->update($saved);

        $onu->unsetRelations();
        $onu->append(['signal_quality', 'display_router_mac']);

        return response()->json([
            'success' => true,
            'message' => $oltResult['pushed_to_olt']
                ? __('ONU configuration saved successfully and synchronized with OLT.')
                : __('Local ONU details saved. Device settings were not changed.'),
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

        if (!$olt || $olt->tenant_id != $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $success = $this->oltApiService->rebootOnu($olt, $onu->pon_port, (int)$onu->onu_id);
        $this->audit($request, $onu, 'ONU_REBOOT', $success);

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

        if (!$olt || $olt->tenant_id != $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $request->validate(['confirm_onu_id' => 'required|integer|in:' . $onu->id]);
        $success = $this->oltApiService->factoryResetOnu($olt, $onu->pon_port, (int)$onu->onu_id);
        $this->audit($request, $onu, 'ONU_FACTORY_RESET', $success);
        if (!$success) {
            return response()->json(['success' => false, 'message' => __('Device changes were not confirmed. Refresh device data before trying again.')], 422);
        }

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

        if (!$olt || $olt->tenant_id != $tenant->id) {
            return response()->json([
                'success' => false,
                'message' => __('Associated OLT not found.'),
            ], 404);
        }

        $telemetry = $this->oltApiService->getOnuOpticalTelemetry($olt, $onu->pon_port, (int)$onu->onu_id);
        if (!$telemetry && ($onu->rx_power_dbm !== null || $onu->tx_power_dbm !== null || $onu->distance_m !== null)) {
            $telemetry = [
                'rx_power_dbm' => $onu->rx_power_dbm, 'tx_power_dbm' => $onu->tx_power_dbm,
                'distance_m' => $onu->distance_m, 'updated_at' => $onu->optical_observed_at?->toIso8601String(),
                'source' => 'cache',
            ];
        }

        $isLive = $telemetry && ($telemetry['source'] ?? 'live') === 'live';
        if ($isLive) {
            $onu->update([
                'rx_power_dbm' => $telemetry['rx_power_dbm'] ?? null,
                'tx_power_dbm' => $telemetry['tx_power_dbm'] ?? null,
                'distance_m' => $telemetry['distance_m'] ?? $onu->distance_m,
                'optical_observed_at' => $telemetry['updated_at'] ?? now(),
            ]);
        }

        $onu->unsetRelations();
        $onu->append('signal_quality');

        return response()->json([
            'success' => $telemetry !== null,
            'source' => $isLive ? 'live' : ($telemetry ? 'cache' : 'unavailable'),
            'message' => $isLive
                ? __('Live optical telemetry fetched successfully.')
                : __('Live reading unavailable. Showing the last stored reading.'),
            'telemetry' => $telemetry,
            'onu' => $onu,
        ]);
    }

    /**
     * Delete selected offline ONU inventory records only.
     */
    public function bulkDeleteOffline(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => __('Unauthorized')], 401);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $query = TenantOnu::where('tenant_id', $tenant->id)
            ->whereIn('id', $validated['ids'])
            ->where('status', '!=', 'online');

        $deleted = $query->count();
        $query->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'message' => trans_choice(':count offline ONU record deleted successfully.|:count offline ONU records deleted successfully.', $deleted, ['count' => $deleted]),
        ]);
    }

    private function audit(Request $request, TenantOnu $onu, string $event, bool $success): void
    {
        \App\Models\TenantActivityLog::create([
            'tenant_id' => $onu->tenant_id, 'actor_type' => \App\Models\User::class,
            'actor_id' => Auth::id(), 'actor_name' => Auth::user()?->name,
            'event_type' => $event, 'description' => $event,
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
            'metadata' => ['onu_id' => $onu->id, 'olt_id' => $onu->olt_id, 'confirmed' => $success],
        ]);
    }
}
