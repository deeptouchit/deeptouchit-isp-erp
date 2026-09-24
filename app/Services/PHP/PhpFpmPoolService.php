<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Models\Website;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpFpmPoolService
{
    use CommandExecutor;

    protected PhpFpmService $fpmService;
    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpFpmService $fpmService, PhpDiscoveryService $discoveryService)
    {
        $this->fpmService = $fpmService;
        $this->discoveryService = $discoveryService;
    }

    /**
     * Get all pools across all installed PHP versions with metrics.
     */
    public function getAllPools(): array
    {
        $installed = $this->discoveryService->discoverInstalledVersions();
        $allPools = [];

        foreach ($installed as $version => $info) {
            $poolDir = "/etc/php/{$version}/fpm/pool.d";
            $isFpmActive = $info['fpm_running'] ?? false;

            if (File::isDirectory($poolDir)) {
                $files = File::files($poolDir);

                foreach ($files as $file) {
                    if ($file->getExtension() === 'conf') {
                        $poolName = $file->getFilenameWithoutExtension();
                        $parsed = $this->parsePoolConfig($file->getPathname(), $version);
                        
                        // Check website usage
                        $attachedSites = Website::where('php_version', $version)
                            ->select(['id', 'domain', 'status'])
                            ->get()
                            ->toArray();

                        $allPools[] = array_merge([
                            'id' => "{$version}-{$poolName}",
                            'version' => $version,
                            'name' => $poolName,
                            'is_default' => $poolName === 'www',
                            'file' => $file->getPathname(),
                            'is_active' => $isFpmActive,
                            'attached_websites' => $attachedSites,
                            'attached_count' => count($attachedSites),
                        ], $parsed);
                    }
                }
            }
        }

        return $allPools;
    }

    /**
     * Discover all FPM pools for a specific PHP version.
     */
    public function getPools(string $version): array
    {
        $poolDir = "/etc/php/{$version}/fpm/pool.d";
        $pools = [];

        if (File::isDirectory($poolDir)) {
            $files = File::files($poolDir);

            foreach ($files as $file) {
                if ($file->getExtension() === 'conf') {
                    $poolName = $file->getFilenameWithoutExtension();
                    $parsed = $this->parsePoolConfig($file->getPathname(), $version);
                    $pools[$poolName] = array_merge([
                        'name' => $poolName, 
                        'version' => $version,
                        'file' => $file->getPathname()
                    ], $parsed);
                }
            }
        }

        return $pools;
    }

    /**
     * Aggregate system-wide FPM Pool metrics for 3-tier cards.
     */
    public function getPoolStats(): array
    {
        $pools = $this->getAllPools();
        $totalPools = count($pools);
        $totalMaxWorkers = 0;
        $pmCounts = ['dynamic' => 0, 'ondemand' => 0, 'static' => 0];
        $activeDaemons = 0;
        $uniqueVersions = [];

        foreach ($pools as $pool) {
            $totalMaxWorkers += (int)($pool['pm_max_children'] ?? 0);
            $mode = $pool['pm'] ?? 'dynamic';
            if (isset($pmCounts[$mode])) {
                $pmCounts[$mode]++;
            } else {
                $pmCounts['dynamic']++;
            }

            if (!empty($pool['is_active']) && !in_array($pool['version'], $uniqueVersions, true)) {
                $uniqueVersions[] = $pool['version'];
                $activeDaemons++;
            }
        }

        return [
            'total_pools' => $totalPools,
            'total_max_workers' => $totalMaxWorkers,
            'pm_counts' => $pmCounts,
            'active_daemons' => $activeDaemons,
            'total_versions' => count($this->discoveryService->discoverInstalledVersions()),
        ];
    }

    /**
     * Parse pool directives from a .conf file.
     */
    protected function parsePoolConfig(string $filePath, string $version): array
    {
        $default = [
            'user' => 'www-data',
            'group' => 'www-data',
            'listen' => "/run/php/php{$version}-fpm.sock",
            'listen_owner' => 'www-data',
            'listen_group' => 'www-data',
            'listen_mode' => '0660',
            'pm' => 'dynamic',
            'pm_max_children' => 50,
            'pm_start_servers' => 5,
            'pm_min_spare_servers' => 5,
            'pm_max_spare_servers' => 35,
            'pm_process_idle_timeout' => '10s',
            'pm_max_requests' => 500,
            'request_terminate_timeout' => '0',
            'memory_limit' => '512M',
            'catch_workers_output' => 'yes',
        ];

        if (!file_exists($filePath)) return $default;

        $content = @file_get_contents($filePath);
        if ($content === false) return $default;

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, ';')) continue;

            if (preg_match('/^([a-zA-Z0-9_.\[\]]+)\s*=\s*(.+)$/', $trimmed, $m)) {
                $key = trim($m[1]);
                $val = trim($m[2]);

                if ($key === 'user') $default['user'] = $val;
                elseif ($key === 'group') $default['group'] = $val;
                elseif ($key === 'listen') $default['listen'] = $val;
                elseif ($key === 'listen.owner') $default['listen_owner'] = $val;
                elseif ($key === 'listen.group') $default['listen_group'] = $val;
                elseif ($key === 'listen.mode') $default['listen_mode'] = $val;
                elseif ($key === 'pm') $default['pm'] = $val;
                elseif ($key === 'pm.max_children') $default['pm_max_children'] = (int)$val;
                elseif ($key === 'pm.start_servers') $default['pm_start_servers'] = (int)$val;
                elseif ($key === 'pm.min_spare_servers') $default['pm_min_spare_servers'] = (int)$val;
                elseif ($key === 'pm.max_spare_servers') $default['pm_max_spare_servers'] = (int)$val;
                elseif ($key === 'pm.process_idle_timeout') $default['pm_process_idle_timeout'] = $val;
                elseif ($key === 'pm.max_requests') $default['pm_max_requests'] = (int)$val;
                elseif ($key === 'request_terminate_timeout') $default['request_terminate_timeout'] = $val;
                elseif ($key === 'php_admin_value[memory_limit]') $default['memory_limit'] = $val;
            }
        }

        return $default;
    }

    /**
     * Update FPM pool directives safely with validation and reload.
     */
    public function updatePool(string $version, string $poolName, array $settings, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $poolName)) {
            return ['success' => false, 'error' => "Invalid pool name: {$poolName}"];
        }

        $poolFile = "/etc/php/{$version}/fpm/pool.d/{$poolName}.conf";
        if (!file_exists($poolFile)) {
            return ['success' => false, 'error' => "Pool configuration not found: {$poolFile}"];
        }

        // Validate limits
        $maxChildren = max(2, min(1000, (int)($settings['pm_max_children'] ?? 50)));
        $startServers = max(1, min($maxChildren, (int)($settings['pm_start_servers'] ?? 5)));
        $minSpare = max(1, min($startServers, (int)($settings['pm_min_spare_servers'] ?? 5)));
        $maxSpare = max($minSpare, min($maxChildren, (int)($settings['pm_max_spare_servers'] ?? 35)));
        $maxRequests = max(0, min(50000, (int)($settings['pm_max_requests'] ?? 500)));
        $idleTimeout = !empty($settings['pm_process_idle_timeout']) ? trim($settings['pm_process_idle_timeout']) : '10s';
        $terminateTimeout = !empty($settings['request_terminate_timeout']) ? trim($settings['request_terminate_timeout']) : '0';
        $pmMode = in_array($settings['pm'] ?? 'dynamic', ['dynamic', 'static', 'ondemand'], true) ? $settings['pm'] : 'dynamic';

        $backupPath = storage_path("app/pool_backups/{$poolName}_{$version}_" . date('Y_m_d_His') . ".conf");
        File::ensureDirectoryExists(dirname($backupPath));
        @copy($poolFile, $backupPath);

        $content = File::get($poolFile);

        $this->updateDirectiveInContent($content, 'pm', $pmMode);
        $this->updateDirectiveInContent($content, 'pm.max_children', (string)$maxChildren);
        $this->updateDirectiveInContent($content, 'pm.start_servers', (string)$startServers);
        $this->updateDirectiveInContent($content, 'pm.min_spare_servers', (string)$minSpare);
        $this->updateDirectiveInContent($content, 'pm.max_spare_servers', (string)$maxSpare);
        $this->updateDirectiveInContent($content, 'pm.process_idle_timeout', $idleTimeout);
        $this->updateDirectiveInContent($content, 'pm.max_requests', (string)$maxRequests);
        $this->updateDirectiveInContent($content, 'request_terminate_timeout', $terminateTimeout);

        if (!empty($settings['memory_limit'])) {
            $this->updateDirectiveInContent($content, 'php_admin_value[memory_limit]', trim($settings['memory_limit']));
        }

        $tempFile = storage_path("app/temp_pool_{$poolName}_{$version}.conf");
        File::put($tempFile, $content);
        $this->executeCommand(['sudo', 'cp', $tempFile, $poolFile]);
        @unlink($tempFile);

        // Validate syntax
        $val = $this->fpmService->validateConfiguration($version);
        if (!$val['success']) {
            $this->executeCommand(['sudo', 'cp', $backupPath, $poolFile]);
            return ['success' => false, 'error' => "Pool syntax error, changes rolled back: " . $val['error']];
        }

        // Graceful reload
        $reload = $this->fpmService->manageService($version, 'reload');
        if (!$reload['success']) {
            $this->executeCommand(['sudo', 'cp', $backupPath, $poolFile]);
            return ['success' => false, 'error' => "FPM reload failed, changes rolled back: " . $reload['error']];
        }

        $this->logAction($adminId, 'php_pool_updated', [
            'version' => $version,
            'pool' => $poolName,
            'pm' => $pmMode,
            'max_children' => $maxChildren,
        ]);

        return [
            'success' => true,
            'message' => "PHP {$version} pool '{$poolName}' successfully tuned and FastCGI daemon reloaded.",
        ];
    }

    /**
     * Create a new isolated FPM pool.
     */
    public function createPool(string $version, string $poolName, array $settings, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $poolName = strtolower(trim($poolName));
        if (!preg_match('/^[a-z0-9_-]+$/', $poolName)) {
            return ['success' => false, 'error' => "Pool name can only contain lowercase letters, numbers, dashes and underscores."];
        }

        $poolFile = "/etc/php/{$version}/fpm/pool.d/{$poolName}.conf";
        if (file_exists($poolFile)) {
            return ['success' => false, 'error' => "A pool with the name '{$poolName}' already exists for PHP {$version}."];
        }

        $user = $settings['user'] ?? 'www-data';
        $group = $settings['group'] ?? 'www-data';
        $listen = $settings['listen'] ?? "/run/php/php{$version}-{$poolName}.sock";
        $pm = in_array($settings['pm'] ?? 'dynamic', ['dynamic', 'static', 'ondemand'], true) ? $settings['pm'] : 'dynamic';
        $maxChildren = max(2, min(1000, (int)($settings['pm_max_children'] ?? 20)));
        $startServers = max(1, min($maxChildren, (int)($settings['pm_start_servers'] ?? 4)));
        $minSpare = max(1, min($startServers, (int)($settings['pm_min_spare_servers'] ?? 2)));
        $maxSpare = max($minSpare, min($maxChildren, (int)($settings['pm_max_spare_servers'] ?? 10)));
        $maxRequests = max(0, min(50000, (int)($settings['pm_max_requests'] ?? 500)));
        $memoryLimit = !empty($settings['memory_limit']) ? trim($settings['memory_limit']) : '256M';

        $template = <<<CONF
; DeepTouchHost Isolated PHP-FPM Pool Configuration
; Generated automatically for PHP {$version}

[{$poolName}]
user = {$user}
group = {$group}

listen = {$listen}
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = {$pm}
pm.max_children = {$maxChildren}
pm.start_servers = {$startServers}
pm.min_spare_servers = {$minSpare}
pm.max_spare_servers = {$maxSpare}
pm.process_idle_timeout = 10s
pm.max_requests = {$maxRequests}

request_terminate_timeout = 300s
catch_workers_output = yes

php_admin_value[memory_limit] = {$memoryLimit}
php_admin_value[error_log] = /var/log/php{$version}-{$poolName}-error.log
php_admin_flag[log_errors] = on
CONF;

        $tempFile = storage_path("app/temp_new_pool_{$poolName}_{$version}.conf");
        File::put($tempFile, $template);
        $this->executeCommand(['sudo', 'cp', $tempFile, $poolFile]);
        @unlink($tempFile);

        // Validate syntax
        $val = $this->fpmService->validateConfiguration($version);
        if (!$val['success']) {
            $this->executeCommand(['sudo', 'rm', '-f', $poolFile]);
            return ['success' => false, 'error' => "Pool syntax error: " . $val['error']];
        }

        // Graceful reload
        $this->fpmService->manageService($version, 'reload');

        $this->logAction($adminId, 'php_pool_created', [
            'version' => $version,
            'pool' => $poolName,
            'socket' => $listen,
        ]);

        return [
            'success' => true,
            'message' => "Isolated PHP {$version} pool '{$poolName}' created successfully.",
        ];
    }

    /**
     * Safely delete a custom FPM pool.
     */
    public function deletePool(string $version, string $poolName, ?int $adminId = null): array
    {
        if ($poolName === 'www') {
            return ['success' => false, 'error' => "The primary default 'www' pool cannot be deleted."];
        }

        $poolFile = "/etc/php/{$version}/fpm/pool.d/{$poolName}.conf";
        if (!file_exists($poolFile)) {
            return ['success' => false, 'error' => "Pool configuration file not found."];
        }

        $this->executeCommand(['sudo', 'rm', '-f', $poolFile]);
        $this->fpmService->manageService($version, 'reload');

        $this->logAction($adminId, 'php_pool_deleted', [
            'version' => $version,
            'pool' => $poolName,
        ]);

        return [
            'success' => true,
            'message' => "Pool '{$poolName}' has been removed and FastCGI reloaded.",
        ];
    }

    /**
     * Restart/Reload the FPM daemon for a version.
     */
    public function restartPool(string $version, ?int $adminId = null): array
    {
        $res = $this->fpmService->manageService($version, 'restart');

        if ($res['success']) {
            $this->logAction($adminId, 'php_fpm_restarted', ['version' => $version]);
            return [
                'success' => true,
                'message' => "PHP {$version} FastCGI Process Manager service restarted successfully.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to restart PHP {$version}-fpm: " . ($res['error'] ?? 'Unknown error'),
        ];
    }

    /**
     * Helper to update or append a directive in pool config content.
     */
    protected function updateDirectiveInContent(string &$content, string $directive, string $value): void
    {
        $escaped = preg_quote($directive, '/');
        $pattern = "/^(\s*;\s*)?({$escaped}\s*=).*$/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$directive} = {$value}", $content);
        } else {
            $content .= "\n{$directive} = {$value}\n";
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PHP Pool Manager: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }
    }
}

