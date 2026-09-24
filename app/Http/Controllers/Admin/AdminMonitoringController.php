<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Monitoring\AdminMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMonitoringController extends Controller
{
    protected AdminMonitoringService $monitoringService;

    public function __construct(AdminMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Display Real-Time System Monitoring & Telemetry Dashboard.
     */
    public function overview(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getSystemOverview();

        return Inertia::render('Admin/Monitoring/Overview', [
            'stats' => $data['stats'],
            'cpu' => $data['cpu'],
            'memory' => $data['memory'],
            'disk' => $data['disk'],
            'network' => $data['network'],
            'services' => $data['services'],
            'processes' => $data['processes'],
            'system' => $data['system'],
        ]);
    }

    /**
     * Display Detailed CPU Processor Performance & Cores Telemetry.
     */
    public function cpu(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getCpuDeepOverview();

        return Inertia::render('Admin/Monitoring/Cpu', [
            'stats' => $data['stats'],
            'cores_data' => $data['cores_data'],
            'hardware' => $data['hardware'],
            'processes' => $data['processes'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for CPU.
     */
    public function apiCpuMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getCpuDeepOverview();

        return response()->json($data);
    }

    /**
     * Display Detailed RAM & Swap Memory Telemetry.
     */
    public function ram(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getMemoryDeepOverview();

        return Inertia::render('Admin/Monitoring/Ram', [
            'stats' => $data['stats'],
            'breakdown' => $data['breakdown'],
            'hardware' => $data['hardware'],
            'processes' => $data['processes'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for RAM & Memory.
     */
    public function apiRamMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getMemoryDeepOverview();

        return response()->json($data);
    }

    /**
     * Display Detailed Disk Storage, Partitions, and Inodes Telemetry.
     */
    public function disk(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getDiskDeepOverview();

        return Inertia::render('Admin/Monitoring/Disk', [
            'stats' => $data['stats'],
            'partitions' => $data['partitions'],
            'inodes' => $data['inodes'],
            'iops' => $data['iops'],
            'directories' => $data['directories'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Disk storage.
     */
    public function apiDiskMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getDiskDeepOverview();

        return response()->json($data);
    }

    /**
     * Display Detailed Network Interface Adapters & Sockets Telemetry.
     */
    public function network(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getNetworkDeepOverview();

        return Inertia::render('Admin/Monitoring/Network', [
            'stats' => $data['stats'],
            'interfaces' => $data['interfaces'],
            'sockets' => $data['sockets'],
            'connections' => $data['connections'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Network.
     */
    public function apiNetworkMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getNetworkDeepOverview();

        return response()->json($data);
    }

    /**
     * Display Multi-Version PHP-FPM Pools & Workers Telemetry.
     */
    public function phpFpm(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getPhpFpmOverview();

        return Inertia::render('Admin/Monitoring/PhpFpm', [
            'stats' => $data['stats'],
            'versions' => $data['versions'],
            'workers' => $data['workers'],
            'logs' => $data['logs'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for PHP-FPM.
     */
    public function apiPhpFpmMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getPhpFpmOverview();

        return response()->json($data);
    }

    /**
     * Restart a specific PHP-FPM pool daemon.
     */
    public function restartPhpFpm(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'version' => 'required|string',
        ]);

        $res = $this->monitoringService->restartPhpFpm($request->input('version'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Gracefully reload a specific PHP-FPM pool daemon.
     */
    public function reloadPhpFpm(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'version' => 'required|string',
        ]);

        $res = $this->monitoringService->reloadPhpFpm($request->input('version'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Display Detailed Database Engines & Queries Telemetry (MySQL, PostgreSQL, Redis).
     */
    public function database(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getDatabaseOverview();

        return Inertia::render('Admin/Monitoring/Database', [
            'stats' => $data['stats'],
            'engines' => $data['engines'],
            'processes' => $data['processes'],
            'databases' => $data['databases'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Database engines.
     */
    public function apiDatabaseMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getDatabaseOverview();

        return response()->json($data);
    }

    /**
     * Terminate a running database query thread.
     */
    public function killDatabaseQuery(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'thread_id' => 'required|integer|min:1',
        ]);

        $res = $this->monitoringService->killDatabaseQuery((int)$request->input('thread_id'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Flush MySQL tables cache.
     */
    public function flushDatabaseTables(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->flushDatabaseTables(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Restart a database daemon service.
     */
    public function restartDatabaseService(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'engine' => 'required|string|in:mysql,postgresql,redis',
        ]);

        $res = $this->monitoringService->restartDatabaseService($request->input('engine'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Display Core Daemons & System Services Fleet Telemetry.
     */
    public function services(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getServicesDeepOverview();

        return Inertia::render('Admin/Monitoring/Services', [
            'stats' => $data['stats'],
            'services' => $data['services'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Services.
     */
    public function apiServicesMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getServicesDeepOverview();

        return response()->json($data);
    }

    /**
     * Execute systemd action on a service (start, stop, restart, reload, enable, disable).
     */
    public function serviceAction(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'service' => 'required|string',
            'action' => 'required|string|in:start,stop,restart,reload,enable,disable',
        ]);

        $res = $this->monitoringService->serviceAction(
            $request->input('service'),
            $request->input('action'),
            auth()->id()
        );

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Read live systemd journal logs for a service.
     */
    public function serviceLogs(Request $request, string $service): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getServiceLogs($service, 50);

        return response()->json($data);
    }

    /**
     * Display Infrastructure Monitoring Alerts, Incidents, and Threshold Rules.
     */
    public function alerts(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getAlertsOverview();

        return Inertia::render('Admin/Monitoring/Alerts', [
            'stats' => $data['stats'],
            'incidents' => $data['incidents'],
            'history' => $data['history'],
            'rules' => $data['rules'],
            'channels' => $data['channels'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Alerts.
     */
    public function apiAlertsMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getAlertsOverview();

        return response()->json($data);
    }

    /**
     * Save or update an alert threshold rule.
     */
    public function saveAlertRule(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'alert_type' => 'required|string',
            'warning_threshold' => 'required|numeric',
            'critical_threshold' => 'required|numeric',
            'duration_seconds' => 'required|integer|min:5',
            'cooldown_seconds' => 'required|integer|min:10',
            'enabled' => 'required|boolean',
            'channels' => 'required|array',
        ]);

        $res = $this->monitoringService->saveAlertRule($request->all(), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Suppress / Snooze an active alert incident.
     */
    public function suppressAlert(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'alert_id' => 'required|integer|min:1',
            'duration' => 'required|integer|min:5',
            'reason' => 'nullable|string',
        ]);

        $res = $this->monitoringService->suppressAlert(
            (int)$request->input('alert_id'),
            (int)$request->input('duration'),
            (string)($request->input('reason') ?: 'Manual admin suppression'),
            auth()->id()
        );

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Mark an alert incident as resolved.
     */
    public function resolveAlert(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'alert_id' => 'required|integer|min:1',
        ]);

        $res = $this->monitoringService->resolveAlert((int)$request->input('alert_id'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Send test alert notification to a channel.
     */
    public function testAlertNotification(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $channel = $request->input('channel', 'email');

        return response()->json([
            'success' => true,
            'message' => "Test alert notification dispatched to {$channel} channel successfully.",
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for full system.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->monitoringService->getSystemOverview();

        return response()->json($data);
    }

    /**
     * Flush memory pagecache and buffers.
     */
    public function dropCaches(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->dropCaches(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Flush and reclaim Swap memory.
     */
    public function flushSwap(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->flushSwap(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Vacuum system journal logs.
     */
    public function vacuumLogs(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->vacuumLogs(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Clean temporary scratch files.
     */
    public function cleanTemp(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->cleanTemp(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Flush DNS Resolver cache.
     */
    public function flushDns(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->monitoringService->flushDnsCache(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Terminate system process.
     */
    public function killProcess(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'pid' => 'required|integer|min:2',
        ]);

        $res = $this->monitoringService->killProcess((int)$request->input('pid'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Adjust CPU priority (nice level) of a process.
     */
    public function reniceProcess(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'pid' => 'required|integer|min:2',
            'nice' => 'required|integer|between:-20,19',
        ]);

        $res = $this->monitoringService->reniceProcess((int)$request->input('pid'), (int)$request->input('nice'), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
