<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantOlt;
use App\Models\TenantOnu;
use App\Models\TenantRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkMapController extends Controller
{
    /**
     * Display interactive Network Topology & GIS Map.
     */
    public function index(Request $request)
    {
        $tenant = Auth::user()?->tenant;
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }

        $selectedRouterId = $request->input('router_id');
        $selectedOltId = $request->input('olt_id');
        $selectedStatus = $request->input('status'); // online, offline, all

        // 1. Fetch Routers
        $routerQuery = TenantRouter::where('tenant_id', $tenant->id);
        if ($selectedRouterId) {
            $routerQuery->where('id', $selectedRouterId);
        }
        $routers = $routerQuery->get();
        $allRouters = TenantRouter::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address', 'status']);

        // 2. Fetch OLTs with router relation
        $oltQuery = TenantOlt::where('tenant_id', $tenant->id)->with('router');
        if ($selectedRouterId) {
            $oltQuery->where('router_id', $selectedRouterId);
        }
        if ($selectedOltId) {
            $oltQuery->where('id', $selectedOltId);
        }
        $olts = $oltQuery->get();
        $allOlts = TenantOlt::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address', 'status', 'router_id']);

        // 3. Fetch ONUs with OLT relation
        $onuQuery = TenantOnu::where('tenant_id', $tenant->id)->with('olt.router');
        if ($selectedOltId) {
            $onuQuery->where('olt_id', $selectedOltId);
        } elseif ($selectedRouterId) {
            $oltIds = TenantOlt::where('tenant_id', $tenant->id)->where('router_id', $selectedRouterId)->pluck('id');
            $onuQuery->whereIn('olt_id', $oltIds);
        }
        if ($selectedStatus === 'online') {
            $onuQuery->where('status', 'online');
        } elseif ($selectedStatus === 'offline') {
            $onuQuery->where('status', 'offline');
        }
        $onus = $onuQuery->get();

        // 4. Calculate Summary Metrics
        $totalRouters = TenantRouter::where('tenant_id', $tenant->id)->count();
        $onlineRouters = TenantRouter::where('tenant_id', $tenant->id)->where('status', 'online')->count();

        $totalOlts = TenantOlt::where('tenant_id', $tenant->id)->count();
        $onlineOlts = TenantOlt::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $totalPonPorts = (int) TenantOlt::where('tenant_id', $tenant->id)->sum('total_pon_ports') ?: (TenantOlt::where('tenant_id', $tenant->id)->count() * 8);

        $totalOnus = TenantOnu::where('tenant_id', $tenant->id)->count();
        $onlineOnus = TenantOnu::where('tenant_id', $tenant->id)->where('status', 'online')->count();
        $offlineOnus = TenantOnu::where('tenant_id', $tenant->id)->where('status', 'offline')->count();
        $losOnus = TenantOnu::where('tenant_id', $tenant->id)->where(function ($q) {
            $q->where('status', 'offline')->orWhere('rx_power_dbm', '<', -28.0);
        })->count();

        $avgRxPower = TenantOnu::where('tenant_id', $tenant->id)
            ->whereNotNull('rx_power_dbm')
            ->where('rx_power_dbm', '!=', 0)
            ->avg('rx_power_dbm');
        $avgRxPower = $avgRxPower ? round($avgRxPower, 1) : -21.5;

        // Helper to extract port number from any pon_port format (e.g. 0/1/1..0/2/4, 0/1..0/4, EPON0/1, 1/1)
        $extractPortNumber = function ($portStr) {
            if (empty($portStr)) {
                return 1;
            }
            if (is_numeric($portStr)) {
                return (int)$portStr;
            }
            $clean = preg_replace('/[^0-9\/]/', '', (string)$portStr);
            $parts = explode('/', $clean);
            if (count($parts) === 3) {
                $slot = (int)($parts[1] ?? 1) ?: 1;
                $port = (int)($parts[2] ?? 1) ?: 1;
                return (($slot - 1) * 4) + $port;
            }
            if (count($parts) === 2) {
                return (int)($parts[1] ?? 1) ?: 1;
            }
            $num = (int) preg_replace('/[^0-9]/', '', (string)$portStr);
            return $num > 0 ? $num : 1;
        };

        $buildPonGroups = function ($olt, $oltOnus) use ($extractPortNumber) {
            $ponGroups = [];
            $configuredPon = (int)($olt->total_pon_ports ?: ($olt->pon_ports_count ?: 4));
            if ($configuredPon < 1) $configuredPon = 4;

            // Find maximum port number having ONUs
            $maxDetectedPort = 0;
            foreach ($oltOnus as $onu) {
                $pNum = $extractPortNumber($onu->pon_port);
                if ($pNum > $maxDetectedPort) {
                    $maxDetectedPort = $pNum;
                }
            }

            $totalPortsToShow = max($configuredPon, $maxDetectedPort);

            for ($i = 1; $i <= $totalPortsToShow; $i++) {
                $portOnus = $oltOnus->filter(function ($onu) use ($i, $extractPortNumber) {
                    return $extractPortNumber($onu->pon_port) === $i;
                })->values();

                if ($portOnus->count() > 0 || $i <= $configuredPon) {
                    $ponGroups[] = [
                        'port_number' => $i,
                        'name' => "PON {$i}",
                        'total_onus' => $portOnus->count(),
                        'online_onus' => $portOnus->where('status', 'online')->count(),
                        'offline_onus' => $portOnus->where('status', 'offline')->count(),
                        'onus' => $portOnus->map(function ($onu) {
                            return [
                                'id' => $onu->id,
                                'name' => $onu->name ?: ($onu->model ?: "ONU #{$onu->onu_id}"),
                                'desc' => $onu->desc,
                                'mac_address' => $onu->mac_address,
                                'vendor' => $onu->vendor,
                                'model' => $onu->model,
                                'status' => $onu->status,
                                'rx_power_dbm' => $onu->rx_power_dbm !== null ? (float)$onu->rx_power_dbm : null,
                                'distance_m' => $onu->distance_m,
                                'pppoe_username' => $onu->pppoe_username,
                                'vlan_id' => $onu->vlan_id,
                            ];
                        })->values()->toArray(),
                    ];
                }
            }

            return $ponGroups;
        };

        // 5. Build Hierarchical Topology Data Structure
        $topology = [];

        foreach ($routers as $router) {
            $routerOlts = $olts->where('router_id', $router->id);
            $oltNodes = [];

            foreach ($routerOlts as $olt) {
                $oltOnus = $onus->where('olt_id', $olt->id);
                $ponGroups = $buildPonGroups($olt, $oltOnus);

                $oltNodes[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'brand' => $olt->brand ?: 'VSOL / BDCOM / HSGQ',
                    'model' => $olt->model,
                    'ip_address' => $olt->ip_address,
                    'status' => $olt->status ?: 'online',
                    'total_pon_ports' => count($ponGroups),
                    'online_onus' => $oltOnus->where('status', 'online')->count(),
                    'total_onus' => $oltOnus->count(),
                    'cpu_load' => $olt->cpu_load ?? 18,
                    'temp' => $olt->temperature ?? 38,
                    'pon_ports' => $ponGroups,
                ];
            }

            $topology[] = [
                'id' => $router->id,
                'name' => $router->name,
                'model' => $router->model ?: 'MikroTik RouterBOARD',
                'ip_address' => $router->ip_address,
                'status' => $router->status ?: 'online',
                'cpu_load' => $router->cpu_load ?? 12,
                'uptime' => $router->uptime ?? '14d 6h',
                'olts' => $oltNodes,
            ];
        }

        // Standalone OLTs without explicit router assignment
        $unassignedOlts = $olts->whereNull('router_id');
        if ($unassignedOlts->count() > 0 && !$selectedRouterId) {
            $standaloneOltNodes = [];
            foreach ($unassignedOlts as $olt) {
                $oltOnus = $onus->where('olt_id', $olt->id);
                $ponGroups = $buildPonGroups($olt, $oltOnus);

                $standaloneOltNodes[] = [
                    'id' => $olt->id,
                    'name' => $olt->name,
                    'brand' => $olt->brand ?: 'GPON / EPON OLT',
                    'model' => $olt->model,
                    'ip_address' => $olt->ip_address,
                    'status' => $olt->status ?: 'online',
                    'total_pon_ports' => count($ponGroups),
                    'online_onus' => $oltOnus->where('status', 'online')->count(),
                    'total_onus' => $oltOnus->count(),
                    'pon_ports' => $ponGroups,
                ];
            }

            if (!empty($standaloneOltNodes)) {
                $topology[] = [
                    'id' => 0,
                    'name' => 'Standalone Distribution Hub',
                    'model' => 'Distribution Network',
                    'ip_address' => '10.0.0.0/8',
                    'status' => 'online',
                    'cpu_load' => 5,
                    'uptime' => 'Active',
                    'olts' => $standaloneOltNodes,
                ];
            }
        }

        // Return JSON if requested via AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'topology' => $topology,
                'metrics' => [
                    'total_routers' => $totalRouters,
                    'online_routers' => $onlineRouters,
                    'total_olts' => $totalOlts,
                    'online_olts' => $onlineOlts,
                    'total_onus' => $totalOnus,
                    'online_onus' => $onlineOnus,
                    'offline_onus' => $offlineOnus,
                    'los_onus' => $losOnus,
                    'avg_rx_power' => $avgRxPower,
                ],
            ]);
        }

        return view('tenant.network.map', compact(
            'tenant',
            'routers',
            'allRouters',
            'olts',
            'allOlts',
            'onus',
            'topology',
            'totalRouters',
            'onlineRouters',
            'totalOlts',
            'onlineOlts',
            'totalPonPorts',
            'totalOnus',
            'onlineOnus',
            'offlineOnus',
            'losOnus',
            'avgRxPower'
        ));
    }
}
