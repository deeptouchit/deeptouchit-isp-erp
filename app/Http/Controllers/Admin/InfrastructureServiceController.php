<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Infrastructure\Servers\ServerHealthInterface;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerService;
use App\Services\Infrastructure\Servers\ServerServiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureServiceController extends Controller
{
    protected ServerServiceManager $serviceManager;
    protected ServerHealthInterface $healthService;

    public function __construct(ServerServiceManager $serviceManager, ServerHealthInterface $healthService)
    {
        $this->serviceManager = $serviceManager;
        $this->healthService = $healthService;
    }

    /**
     * Display a comprehensive matrix of background daemons and system services across all nodes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Server::class);

        $query = ServerService::query()
            ->with('server:id,name,hostname,ip_address,status,health_status,is_master');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%")
                    ->orWhere('port', 'like', "%{$search}%");
            });
        }

        // Type Filter
        if ($type = $request->input('type')) {
            $query->where('service_type', $type);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Server Filter
        if ($serverId = $request->input('server_id')) {
            $query->where('server_id', $serverId);
        }

        $services = $query->orderBy('service_name', 'asc')->get();

        // Summary Aggregates
        $allServices = ServerService::all();
        $servers = Server::select('id', 'name', 'hostname', 'ip_address')->orderBy('is_master', 'desc')->get();

        $stats = [
            'total_services' => $allServices->count(),
            'running_services' => $allServices->where('status', ServerServiceStatus::RUNNING)->count(),
            'stopped_services' => $allServices->where('status', ServerServiceStatus::STOPPED)->count(),
            'failed_services' => $allServices->where('status', ServerServiceStatus::FAILED)->count(),
            'types' => [
                'web' => $allServices->where('service_type', 'webserver')->count(),
                'database' => $allServices->where('service_type', 'database')->count(),
                'cache' => $allServices->where('service_type', 'cache')->count(),
                'runtime' => $allServices->where('service_type', 'runtime')->count(),
                'security' => $allServices->where('service_type', 'security')->count(),
                'system' => $allServices->where('service_type', 'system')->count(),
            ],
            'last_inspected_at' => now()->toIso8601String(),
        ];

        return Inertia::render('Admin/Infrastructure/Services/Index', [
            'services' => $services,
            'stats' => $stats,
            'servers' => $servers,
            'filters' => $request->only(['search', 'type', 'status', 'server_id']),
        ]);
    }

    /**
     * Manage an individual infrastructure daemon (restart, stop, start, reload).
     */
    public function manage(Request $request, ServerService $service): RedirectResponse
    {
        $this->authorize('manageServices', $service->server);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:start,stop,restart,reload'],
        ]);

        $action = $validated['action'];

        $this->serviceManager->manageService($service->server, $service->service_name, $action);

        return redirect()->back()->with('success', "Service '{$service->service_name}' ({$service->display_name}) {$action} executed successfully on node '{$service->server->name}'.");
    }

    /**
     * Re-sync and discover all real services across all cluster nodes.
     */
    public function syncAll(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Server::class);

        $servers = Server::all();
        foreach ($servers as $server) {
            $this->healthService->checkHealth($server);
        }

        return redirect()->back()->with('success', 'All system daemons and Multi-PHP sockets verified across cluster.');
    }
}
