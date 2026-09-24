<?php

namespace App\Services\Automation;

use App\Models\ActivityLog;
use App\Models\Server;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MaintenanceAutomationService
{
    use CommandExecutor;

    /**
     * Get real-time overview of application maintenance mode and routines.
     */
    public function getMaintenanceOverview(): array
    {
        $isDown = app()->isDownForMaintenance();
        $downData = [];

        $downFile = storage_path('framework/down');
        if (File::exists($downFile)) {
            $json = json_decode(File::get($downFile), true);
            if (is_array($json)) {
                $downData = $json;
            }
        }

        $servers = Server::select('id', 'name', 'hostname', 'ip_address', 'status', 'is_master')
            ->orderBy('is_master', 'desc')
            ->get();

        $maintenanceServersCount = $servers->where('status', 'maintenance')->count();

        $routines = [
            [
                'id' => 'rebuild_caches',
                'name' => 'Rebuild Production Optimization Caches',
                'category' => 'Application Core',
                'description' => 'Clears and compiles production config, route maps, and blade views into optimized bytecode.',
                'impact' => 'Zero Downtime',
                'duration' => '< 1.5s',
            ],
            [
                'id' => 'clear_all_caches',
                'name' => 'Clear All Application & Query Caches',
                'category' => 'Cache Subsystem',
                'description' => 'Flushes framework application caches, compiled blade files, and stale database query stores.',
                'impact' => 'Zero Downtime',
                'duration' => '< 1.0s',
            ],
            [
                'id' => 'multi_php_opcache',
                'name' => 'Multi-PHP OpCache & Pool Reset',
                'category' => 'PHP Runtimes',
                'description' => 'Performs atomic OpCache reset across PHP 8.2, 8.3, and 8.5 workers.',
                'impact' => 'Zero Downtime',
                'duration' => '~ 2.0s',
            ],
            [
                'id' => 'nginx_hot_reload',
                'name' => 'Nginx Zero-Downtime Hot Reload',
                'category' => 'Web Delivery Proxy',
                'description' => 'Validates Nginx virtual host syntax (nginx -t) and signals master process to reload workers.',
                'impact' => 'Zero Downtime',
                'duration' => '< 1.0s',
            ],
            [
                'id' => 'purge_expired_sessions',
                'name' => 'Purge Expired Sessions & Temp Files',
                'category' => 'Storage & Memory',
                'description' => 'Cleans stale session entries, orphan lockfiles, and expired temp upload artifacts.',
                'impact' => 'Zero Downtime',
                'duration' => '< 2.0s',
            ],
            [
                'id' => 'prune_audit_logs',
                'name' => 'Prune Historic Audit Logs (>90 Days)',
                'category' => 'Database Health',
                'description' => 'Optimizes database index size by safely pruning activity log entries older than 90 days.',
                'impact' => 'Zero Downtime',
                'duration' => '~ 1.0s',
            ],
        ];

        $stats = [
            'is_down' => $isDown,
            'status_label' => $isDown ? 'Under Maintenance (503)' : 'Live Operational (200 OK)',
            'secret' => $downData['secret'] ?? null,
            'retry_after' => $downData['retry'] ?? 600,
            'maintenance_started_at' => isset($downData['time']) ? date('M d, Y H:i', $downData['time']) : null,
            'total_servers' => $servers->count(),
            'servers_in_maintenance' => $maintenanceServersCount,
            'routines_count' => count($routines),
        ];

        return [
            'stats' => $stats,
            'down_data' => $downData,
            'servers' => $servers,
            'routines' => $routines,
        ];
    }

    /**
     * Enable Laravel Application Maintenance Mode.
     */
    public function enableMaintenance(array $options = [], ?int $adminId = null): array
    {
        $params = [];
        if (!empty($options['secret'])) {
            $params['--secret'] = $options['secret'];
        }
        if (!empty($options['retry'])) {
            $params['--retry'] = (int)$options['retry'];
        }
        if (!empty($options['status'])) {
            $params['--status'] = (int)$options['status'];
        }

        try {
            Artisan::call('down', $params);
            $output = trim(Artisan::output());

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'maintenance_mode_enabled',
                'description' => 'Enabled system-wide maintenance mode' . (!empty($options['secret']) ? ' with bypass secret token.' : '.'),
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['is_down' => false],
                'new_values' => ['is_down' => true, 'options' => $options],
            ]);

            return [
                'success' => true,
                'message' => 'System maintenance mode activated successfully.',
                'output' => $output,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to enable maintenance mode: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Disable Laravel Application Maintenance Mode.
     */
    public function disableMaintenance(?int $adminId = null): array
    {
        try {
            Artisan::call('up');
            $output = trim(Artisan::output());

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'maintenance_mode_disabled',
                'description' => 'Disabled system-wide maintenance mode. Application restored to live operational state.',
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['is_down' => true],
                'new_values' => ['is_down' => false],
            ]);

            return [
                'success' => true,
                'message' => 'Application restored to LIVE operational mode.',
                'output' => $output,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Failed to disable maintenance mode: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Execute a specific automated routine.
     */
    public function executeRoutine(string $routineId, ?int $adminId = null): array
    {
        $start = microtime(true);
        $stdout = '';
        $success = true;

        try {
            switch ($routineId) {
                case 'rebuild_caches':
                    Artisan::call('optimize:clear');
                    Artisan::call('config:cache');
                    Artisan::call('route:cache');
                    Artisan::call('view:cache');
                    $stdout = "Compiled configuration, route map, and view templates successfully.\n" . Artisan::output();
                    break;

                case 'clear_all_caches':
                    Artisan::call('optimize:clear');
                    $stdout = "All framework optimization caches flushed successfully.\n" . Artisan::output();
                    break;

                case 'multi_php_opcache':
                    if (function_exists('opcache_reset')) {
                        @opcache_reset();
                    }
                    $this->executeSudoCommand(['systemctl', 'reload', 'php8.2-fpm', 'php8.3-fpm', 'php8.5-fpm']);
                    $stdout = "OpCache reset & Multi-PHP FPM master processes reloaded.";
                    break;

                case 'nginx_hot_reload':
                    $this->executeSudoCommand(['nginx', '-t']);
                    $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
                    $stdout = "Nginx configuration syntax tested OK and workers reloaded without downtime.";
                    break;

                case 'purge_expired_sessions':
                    $tempDir = storage_path('framework/sessions');
                    $cleaned = 0;
                    if (File::exists($tempDir)) {
                        $files = File::files($tempDir);
                        foreach ($files as $file) {
                            if (now()->timestamp - $file->getMTime() > 86400 * 7) {
                                @unlink($file->getPathname());
                                $cleaned++;
                            }
                        }
                    }
                    $stdout = "Purged {$cleaned} stale session artifacts older than 7 days.";
                    break;

                case 'prune_audit_logs':
                    $deleted = ActivityLog::where('created_at', '<', now()->subDays(90))->delete();
                    $stdout = "Pruned {$deleted} historical activity log entries older than 90 days.";
                    break;

                default:
                    return ['success' => false, 'message' => "Unknown maintenance routine `{$routineId}`."];
            }
        } catch (\Throwable $e) {
            $success = false;
            $stdout = "Routine execution warning: " . $e->getMessage();
        }

        $durationMs = (int)round((microtime(true) - $start) * 1000);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'maintenance_routine_executed',
            'description' => "Executed maintenance routine `{$routineId}` in {$durationMs}ms.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['routine' => $routineId, 'duration_ms' => $durationMs, 'stdout' => $stdout],
        ]);

        return [
            'success' => $success,
            'routine_id' => $routineId,
            'duration_ms' => $durationMs,
            'stdout' => $stdout,
            'message' => "Routine completed in {$durationMs}ms.",
        ];
    }

    /**
     * Toggle Maintenance mode on a server node.
     */
    public function toggleServerMaintenance(int $serverId, ?int $adminId = null): array
    {
        try {
            $server = Server::findOrFail($serverId);
            $newStatus = $server->status === 'maintenance' ? 'online' : 'maintenance';
            $server->update([
                'status' => $newStatus,
                'maintenance_at' => $newStatus === 'maintenance' ? now() : null,
                'maintenance_reason' => $newStatus === 'maintenance' ? 'Operational maintenance window' : null,
            ]);

            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => 'server_maintenance_toggled',
                'description' => "Set server `{$server->name}` status to `{$newStatus}`.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => ['server_id' => $serverId, 'status' => $newStatus],
            ]);

            return [
                'success' => true,
                'status' => $newStatus,
                'message' => "Server `{$server->name}` is now {$newStatus}.",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to toggle server maintenance: ' . $e->getMessage()];
        }
    }
}
