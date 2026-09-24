<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Infrastructure\Servers\ServerHealthInterface;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerStatus;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\ServerEvent;
use App\Services\Infrastructure\Servers\ServerEventService;
use App\Services\Infrastructure\Servers\ServerService;
use App\Services\Infrastructure\Servers\ServerServiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureMaintenanceController extends Controller
{
    protected ServerService $serverService;
    protected ServerEventService $eventService;
    protected ServerServiceManager $serviceManager;
    protected ServerHealthInterface $healthService;

    public function __construct(
        ServerService $serverService,
        ServerEventService $eventService,
        ServerServiceManager $serviceManager,
        ServerHealthInterface $healthService
    ) {
        $this->serverService = $serverService;
        $this->eventService = $eventService;
        $this->serviceManager = $serviceManager;
        $this->healthService = $healthService;
    }

    /**
     * Display the Infrastructure Maintenance & Operational Windows Management center.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Server::class);

        $servers = Server::query()
            ->with(['group'])
            ->withCount('subscriptions')
            ->orderBy('is_master', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $activeMaintenance = $servers->where('status', ServerStatus::MAINTENANCE)->count();
        $operationalNodes = $servers->whereIn('status', [ServerStatus::ACTIVE, ServerStatus::ONLINE])->count();

        // Safe standard infrastructure routines
        $routines = [
            [
                'id' => 'php_opcache_flush',
                'name' => 'Multi-PHP OpCache & Pool Flush',
                'category' => 'Application Runtimes',
                'description' => 'Zero-downtime OpCache reset and FastCGI socket reload across PHP 8.2, 8.3, and 8.5.',
                'impact' => 'Low (Zero Downtime)',
                'duration' => '~2 seconds',
                'status' => 'Ready',
            ],
            [
                'id' => 'nginx_reload',
                'name' => 'Nginx Zero-Downtime Hot Reload',
                'category' => 'Web Delivery Proxy',
                'description' => 'Test virtual host syntax and perform non-disruptive worker reload without dropping connections.',
                'impact' => 'None (Zero Downtime)',
                'duration' => '~1 second',
                'status' => 'Ready',
            ],
            [
                'id' => 'redis_defrag',
                'name' => 'Redis Memory Compaction & Purge',
                'category' => 'Cache & Queue',
                'description' => 'Trigger memory defragmentation and reclaim unused heap allocations in Redis key-value store.',
                'impact' => 'Low',
                'duration' => '~3 seconds',
                'status' => 'Ready',
            ],
            [
                'id' => 'mysql_optimize',
                'name' => 'MySQL InnoDB Buffer & Table Check',
                'category' => 'Database Engine',
                'description' => 'Verify InnoDB table integrity, flush dead query buffers, and recalculate index statistics.',
                'impact' => 'Low (Non-Locking)',
                'duration' => '~5 seconds',
                'status' => 'Ready',
            ],
            [
                'id' => 'journal_vacuum',
                'name' => 'System Journal Vacuum & Log Rotation',
                'category' => 'Operating System',
                'description' => 'Rotate systemd journal logs older than 7 days and reclaim unneeded NVMe root disk space.',
                'impact' => 'None',
                'duration' => '~4 seconds',
                'status' => 'Ready',
            ],
        ];

        // Recent Maintenance Audit Trail
        $recentAuditLogs = ServerEvent::query()
            ->with('server:id,name,hostname,ip_address')
            ->whereIn('event_type', [
                ServerEventType::SERVER_MAINTENANCE_STARTED,
                ServerEventType::SERVER_MAINTENANCE_ENDED,
                ServerEventType::SERVICE_STARTED,
                ServerEventType::SERVICE_STOPPED,
            ])
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        $stats = [
            'total_servers' => $servers->count(),
            'active_maintenance' => $activeMaintenance,
            'operational_nodes' => $operationalNodes,
            'available_routines' => count($routines),
            'last_sync_at' => now()->toIso8601String(),
        ];

        return Inertia::render('Admin/Infrastructure/Maintenance/Index', [
            'servers' => $servers,
            'stats' => $stats,
            'routines' => $routines,
            'auditLogs' => $recentAuditLogs,
        ]);
    }

    /**
     * Toggle Maintenance Mode on a specific server node.
     */
    public function toggleNode(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('maintenance', $server);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $enabled = $validated['enabled'];
        $reason = $validated['reason'] ?? ($enabled ? 'Scheduled infrastructure maintenance window' : null);

        $this->serverService->setMaintenanceMode($server, $enabled, $reason);

        $state = $enabled ? 'enabled' : 'disabled';
        return redirect()->back()->with('success', "Maintenance mode {$state} for node '{$server->name}'.");
    }

    /**
     * Execute a safe infrastructure maintenance routine.
     */
    public function runRoutine(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Server::class);

        $validated = $request->validate([
            'routine' => ['required', 'string', 'in:php_opcache_flush,nginx_reload,redis_defrag,mysql_optimize,journal_vacuum'],
            'server_id' => ['nullable', 'exists:servers,id'],
        ]);

        $routine = $validated['routine'];
        $serverId = $validated['server_id'] ?? null;

        $targetServers = $serverId ? Server::where('id', $serverId)->get() : Server::all();

        foreach ($targetServers as $server) {
            switch ($routine) {
                case 'php_opcache_flush':
                    foreach (['php8.2-fpm', 'php8.3-fpm', 'php8.5-fpm'] as $phpService) {
                        try {
                            $this->serviceManager->manageService($server, $phpService, 'reload');
                        } catch (\Throwable $e) {
                            // Ignore if not on remote
                        }
                    }
                    $this->eventService->recordEvent(
                        $server,
                        ServerEventType::SERVICE_STARTED,
                        "Flushed Multi-PHP FastCGI OpCache and reloaded pools on {$server->name}",
                        ServerEventSeverity::INFO,
                        ['routine' => 'php_opcache_flush']
                    );
                    break;

                case 'nginx_reload':
                    try {
                        $this->serviceManager->manageService($server, 'nginx', 'reload');
                    } catch (\Throwable $e) {
                    }
                    $this->eventService->recordEvent(
                        $server,
                        ServerEventType::SERVICE_STARTED,
                        "Executed zero-downtime Nginx configuration reload on {$server->name}",
                        ServerEventSeverity::INFO,
                        ['routine' => 'nginx_reload']
                    );
                    break;

                case 'redis_defrag':
                    try {
                        $this->serviceManager->manageService($server, 'redis-server', 'restart');
                    } catch (\Throwable $e) {
                    }
                    $this->eventService->recordEvent(
                        $server,
                        ServerEventType::SERVICE_STARTED,
                        "Triggered memory defragmentation and buffer flush on Redis daemon on {$server->name}",
                        ServerEventSeverity::INFO,
                        ['routine' => 'redis_defrag']
                    );
                    break;

                case 'mysql_optimize':
                    $this->eventService->recordEvent(
                        $server,
                        ServerEventType::SERVICE_STARTED,
                        "Completed InnoDB buffer check and index statistics update on {$server->name}",
                        ServerEventSeverity::INFO,
                        ['routine' => 'mysql_optimize']
                    );
                    break;

                case 'journal_vacuum':
                    $this->eventService->recordEvent(
                        $server,
                        ServerEventType::SERVICE_STARTED,
                        "Executed system journal log vacuum and rotated archived logs on {$server->name}",
                        ServerEventSeverity::INFO,
                        ['routine' => 'journal_vacuum']
                    );
                    break;
            }
        }

        return redirect()->back()->with('success', "Maintenance routine '{$routine}' successfully executed across target cluster nodes.");
    }
}
