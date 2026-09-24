<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminServiceController extends Controller
{
    /**
     * Pre-defined critical hosting and system services to monitor.
     */
    protected array $monitoredServices = [
        'nginx.service' => [
            'name' => 'Nginx Web Server',
            'category' => 'Web Stack & Proxies',
            'desc' => 'High-performance HTTP server, SSL termination & reverse proxy',
            'icon' => 'globe',
        ],
        'mysql.service' => [
            'name' => 'MySQL / MariaDB',
            'category' => 'Databases & Cache',
            'desc' => 'Relational database management server',
            'icon' => 'database',
        ],
        'redis-server.service' => [
            'name' => 'Redis Cache & Store',
            'category' => 'Databases & Cache',
            'desc' => 'In-memory persistent key-value cache and queue engine',
            'icon' => 'bolt',
        ],
        'php8.2-fpm.service' => [
            'name' => 'PHP 8.2 FastCGI Manager',
            'category' => 'PHP-FPM Engines',
            'desc' => 'PHP 8.2-FPM worker pool management daemon',
            'icon' => 'code',
        ],
        'php8.3-fpm.service' => [
            'name' => 'PHP 8.3 FastCGI Manager',
            'category' => 'PHP-FPM Engines',
            'desc' => 'PHP 8.3-FPM worker pool management daemon',
            'icon' => 'code',
        ],
        'php8.5-fpm.service' => [
            'name' => 'PHP 8.5 FastCGI Manager',
            'category' => 'PHP-FPM Engines',
            'desc' => 'PHP 8.5-FPM worker pool management daemon',
            'icon' => 'code',
        ],
        'postfix.service' => [
            'name' => 'Postfix Mail Transfer (MTA)',
            'category' => 'Mail & Networking',
            'desc' => 'Standard SMTP mail routing and outbound transfer agent',
            'icon' => 'envelope',
        ],
        'vsftpd.service' => [
            'name' => 'vsftpd Secure FTP',
            'category' => 'Mail & Networking',
            'desc' => 'Very Secure FTP daemon for client file transfers',
            'icon' => 'folder',
        ],
        'bind9.service' => [
            'name' => 'BIND 9 DNS Server',
            'category' => 'Mail & Networking',
            'desc' => 'Authoritative domain name resolution server',
            'icon' => 'rss',
        ],
        'ssh.service' => [
            'name' => 'OpenSSH Remote Shell',
            'category' => 'Security & Access',
            'desc' => 'Secure shell daemon for encrypted administrative terminal access',
            'icon' => 'key',
        ],
        'ufw.service' => [
            'name' => 'UFW Linux Firewall',
            'category' => 'Security & Access',
            'desc' => 'Netfilter iptables packet filtering and firewall daemon',
            'icon' => 'shield',
        ],
        'cron.service' => [
            'name' => 'Cron Task Scheduler',
            'category' => 'Automation & Daemons',
            'desc' => 'Standard Linux scheduled job and time-based task runner',
            'icon' => 'clock',
        ],
        'supervisor.service' => [
            'name' => 'Supervisor Process Controller',
            'category' => 'Automation & Daemons',
            'desc' => 'Worker daemon manager for background asynchronous queues',
            'icon' => 'cpu',
        ],
        'systemd-journald.service' => [
            'name' => 'Systemd Journal Logger',
            'category' => 'Automation & Daemons',
            'desc' => 'Linux unified structured event logging and journal daemon',
            'icon' => 'document',
        ],
    ];

    /**
     * Display live Systemd Service Manager.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $services = $this->getServicesState();

        // 4 Clean 3-Tier Metric Stats calculated live from Linux Systemd
        $totalServices = count($services);
        $activeServices = count(array_filter($services, fn($s) => $s['active_state'] === 'active'));
        $failedServices = count(array_filter($services, fn($s) => $s['active_state'] === 'failed'));
        $enabledServices = count(array_filter($services, fn($s) => $s['unit_file_state'] === 'enabled'));

        $stats = [
            'total_services' => $totalServices,
            'active_services' => $activeServices,
            'failed_services' => $failedServices,
            'enabled_services' => $enabledServices,
        ];

        // Unique categories for filter ribbon
        $categories = array_values(array_unique(array_column($services, 'category')));

        // Filter: Category
        $currentCategory = $request->input('category', 'all');
        if ($currentCategory !== 'all' && $currentCategory !== '') {
            $services = array_filter($services, fn($s) => $s['category'] === $currentCategory);
        }

        // Filter: Status
        $currentStatus = $request->input('status', 'all');
        if ($currentStatus === 'active') {
            $services = array_filter($services, fn($s) => $s['active_state'] === 'active');
        } elseif ($currentStatus === 'inactive') {
            $services = array_filter($services, fn($s) => $s['active_state'] === 'inactive');
        } elseif ($currentStatus === 'failed') {
            $services = array_filter($services, fn($s) => $s['active_state'] === 'failed');
        }

        // Filter: Search
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $services = array_filter($services, function ($s) use ($search) {
                return str_contains(strtolower($s['name']), strtolower($search))
                    || str_contains(strtolower($s['unit']), strtolower($search))
                    || str_contains(strtolower($s['desc']), strtolower($search))
                    || str_contains((string) $s['main_pid'], $search);
            });
        }

        return Inertia::render('Admin/RootTools/Services/Index', [
            'services' => array_values($services),
            'stats' => $stats,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $currentCategory,
                'status' => $currentStatus,
            ],
        ]);
    }

    /**
     * Dispatch start/stop/restart/reload/enable/disable action to a systemd unit.
     */
    public function action(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $allowedUnits = array_keys($this->monitoredServices);

        $validated = $request->validate([
            'unit' => ['required', 'string', 'in:' . implode(',', $allowedUnits)],
            'action' => ['required', 'string', 'in:start,stop,restart,reload,enable,disable'],
        ]);

        $unit = escapeshellarg($validated['unit']);
        $action = $validated['action'];

        // Execute systemctl with --no-ask-password
        if (app()->environment('testing')) {
            $returnCode = 0;
            $output = ["Testing environment: simulated {$action} on {$validated['unit']}"];
        } else {
            $cmd = "systemctl --no-ask-password {$action} {$unit} 2>&1";
            exec($cmd, $output, $returnCode);
        }

        $serviceTitle = $this->monitoredServices[$validated['unit']]['name'] ?? $validated['unit'];

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => "service_{$action}",
            'description' => "Dispatched '{$action}' command on service {$serviceTitle} ({$validated['unit']}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['unit' => $validated['unit']],
            'new_values' => ['action' => $action, 'return_code' => $returnCode, 'output' => implode("\n", $output)],
        ]);

        if ($returnCode === 0) {
            return redirect()->route('admin.root-tools.services')
                ->with('success', "Service {$serviceTitle} {$action}ed successfully.");
        }

        $errorMsg = !empty($output) ? implode(' ', $output) : "systemctl exited with code {$returnCode}";
        return redirect()->route('admin.root-tools.services')
            ->with('error', "Failed to {$action} {$serviceTitle}: {$errorMsg}");
    }

    /**
     * Fetch recent journalctl logs for a specific unit.
     */
    public function logs(Request $request, string $unit): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        if (!array_key_exists($unit, $this->monitoredServices)) {
            return response()->json(['error' => 'Service not in monitored list.'], 404);
        }

        $safeUnit = escapeshellarg($unit);
        $output = shell_exec("journalctl -u {$safeUnit} -n 40 --no-pager 2>&1");

        return response()->json([
            'unit' => $unit,
            'name' => $this->monitoredServices[$unit]['name'] ?? $unit,
            'logs' => $output ? trim($output) : 'No journal logs found for this unit.',
        ]);
    }

    /**
     * Query systemctl show properties for all monitored services.
     */
    private function getServicesState(): array
    {
        $units = array_keys($this->monitoredServices);
        $list = [];

        foreach ($units as $unit) {
            $meta = $this->monitoredServices[$unit];
            $safeUnit = escapeshellarg($unit);

            $showOutput = shell_exec("systemctl show {$safeUnit} -p Id,Description,ActiveState,SubState,UnitFileState,MainPID,ActiveEnterTimestamp,MemoryCurrent,TasksCurrent 2>/dev/null");

            $props = [];
            if ($showOutput) {
                $lines = explode("\n", trim($showOutput));
                foreach ($lines as $line) {
                    if (str_contains($line, '=')) {
                        [$k, $v] = explode('=', $line, 2);
                        $props[$k] = $v;
                    }
                }
            }

            $activeState = $props['ActiveState'] ?? 'inactive';
            $subState = $props['SubState'] ?? 'dead';
            $unitFileState = $props['UnitFileState'] ?? 'unknown';
            $mainPid = (int) ($props['MainPID'] ?? 0);

            // Memory Current in MB
            $memBytes = (int) ($props['MemoryCurrent'] ?? 0);
            $memoryMb = $memBytes > 0 ? round($memBytes / 1024 / 1024, 1) : 0;

            $tasks = (int) ($props['TasksCurrent'] ?? 0);

            // Human Active Time
            $uptimeHuman = 'Inactive';
            if ($activeState === 'active' && !empty($props['ActiveEnterTimestamp'])) {
                try {
                    $uptimeHuman = Carbon::parse($props['ActiveEnterTimestamp'])->diffForHumans(null, true);
                } catch (\Exception $e) {
                    $uptimeHuman = 'Active';
                }
            }

            $list[] = [
                'unit' => $unit,
                'name' => $meta['name'],
                'category' => $meta['category'],
                'desc' => $props['Description'] ?? $meta['desc'],
                'icon' => $meta['icon'],
                'active_state' => $activeState,
                'sub_state' => $subState,
                'unit_file_state' => $unitFileState,
                'main_pid' => $mainPid,
                'memory_mb' => $memoryMb,
                'tasks' => $tasks,
                'uptime' => $uptimeHuman,
            ];
        }

        return $list;
    }
}
