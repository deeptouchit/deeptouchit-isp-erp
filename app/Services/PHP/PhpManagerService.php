<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Website;
use App\Services\NginxManager;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpManagerService
{
    use CommandExecutor;

    protected PhpDiscoveryService $discoveryService;
    protected PhpFpmService $fpmService;
    protected PhpConfigurationService $configService;
    protected PhpStateSyncService $syncService;
    protected NginxManager $nginxManager;

    public function __construct(
        PhpDiscoveryService $discoveryService,
        PhpFpmService $fpmService,
        PhpConfigurationService $configService,
        PhpStateSyncService $syncService,
        NginxManager $nginxManager
    ) {
        $this->discoveryService = $discoveryService;
        $this->fpmService = $fpmService;
        $this->configService = $configService;
        $this->syncService = $syncService;
        $this->nginxManager = $nginxManager;
    }

    /**
     * Get Discovery Service instance.
     */
    public function discovery(): PhpDiscoveryService
    {
        return $this->discoveryService;
    }

    /**
     * Get FPM Service instance.
     */
    public function fpm(): PhpFpmService
    {
        return $this->fpmService;
    }

    /**
     * Get Configuration Service instance.
     */
    public function config(): PhpConfigurationService
    {
        return $this->configService;
    }

    /**
     * Get Sync Service instance.
     */
    public function sync(): PhpStateSyncService
    {
        return $this->syncService;
    }

    /**
     * Get Global Dashboard Telemetry.
     */
    public function getDashboardSummary(): array
    {
        $discovered = $this->discoveryService->discoverInstalledVersions();
        $totalWebsites = Website::count();
        $runningFpm = 0;
        $healthyServices = 0;
        $warnings = 0;

        $versionBreakdown = [];
        foreach ($discovered as $v => $info) {
            $siteCount = Website::where('php_version', $v)->count();
            $versionBreakdown[$v] = [
                'info' => $info,
                'hosted_sites' => $siteCount,
            ];

            if ($info['fpm_status'] === 'running') {
                $runningFpm++;
            }
            if ($info['is_healthy']) {
                $healthyServices++;
            } else {
                $warnings++;
            }
        }

        $defaultVersion = SystemSetting::get('default_php_version', config('panel.hosting.default_php_version', '8.2'));
        $driftReports = $this->syncService->detectDrift();

        return [
            'total_installed' => count($discovered),
            'running_fpm_services' => $runningFpm,
            'healthy_services' => $healthyServices,
            'warnings_count' => $warnings,
            'default_php_version' => $defaultVersion,
            'total_websites' => $totalWebsites,
            'drift_count' => count($driftReports),
            'drift_reports' => $driftReports,
            'versions' => $versionBreakdown,
        ];
    }

    /**
     * Switch Website PHP version with automated VHost backup, Nginx test, and rollback.
     */
    public function switchWebsitePhpVersion(Website $website, string $targetVersion, ?int $adminId = null): array
    {
        $oldVersion = $website->php_version ?: '8.2';

        if ($oldVersion === $targetVersion) {
            return ['success' => true, 'message' => "Website is already on PHP {$targetVersion}."];
        }

        // 1. Validate target PHP version is installed on host
        $installed = $this->discoveryService->discoverInstalledVersions();
        if (!isset($installed[$targetVersion])) {
            return ['success' => false, 'error' => "Target PHP version {$targetVersion} is not installed on this server."];
        }

        $targetSocket = $installed[$targetVersion]['fpm_socket'];
        if (!file_exists($targetSocket)) {
            return ['success' => false, 'error' => "Target PHP {$targetVersion} FPM socket does not exist or service is stopped: {$targetSocket}"];
        }

        $domain = $website->domain;
        $sitesAvailable = '/etc/nginx/sites-available';
        $vhostPath = "{$sitesAvailable}/{$domain}.conf";

        if (!file_exists($vhostPath)) {
            return ['success' => false, 'error' => "Nginx VHost configuration not found for {$domain}."];
        }

        // 2. Backup current VHost
        $backupPath = storage_path("app/vhost_backups/{$domain}_" . date('Y_m_d_His') . ".conf");
        File::ensureDirectoryExists(dirname($backupPath));
        File::copy($vhostPath, $backupPath);

        // 3. Read current VHost and replace FastCGI pass line
        $content = File::get($vhostPath);
        $pattern = '/fastcgi_pass\s+unix:[^;]+;/';
        $replacement = "fastcgi_pass unix:{$targetSocket};";

        if (preg_match($pattern, $content)) {
            $updatedContent = preg_replace($pattern, $replacement, $content);
        } else {
            // If pattern not matched, return error
            return ['success' => false, 'error' => "Could not locate fastcgi_pass directive in {$domain}.conf"];
        }

        // 4. Write updated config to temporary file and copy to sites-available
        $tempFile = storage_path("app/temp_vhost_{$domain}.conf");
        File::put($tempFile, $updatedContent);

        $copyRes = $this->executeSudoCommand(['cp', $tempFile, $vhostPath]);
        @unlink($tempFile);

        if (!$copyRes['success']) {
            return ['success' => false, 'error' => "Failed to update VHost configuration: " . $copyRes['error']];
        }

        // 5. Test Nginx syntax
        $testResult = $this->executeSudoCommand(['nginx', '-t']);
        if (!$testResult['success']) {
            Log::warning("Nginx test failed after switching {$domain} to PHP {$targetVersion}, rolling back: " . $testResult['error']);
            
            // Automated Rollback
            $this->executeSudoCommand(['cp', $backupPath, $vhostPath]);
            $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);

            $this->logAction($adminId, 'php_switch_failed', [
                'domain' => $domain,
                'target_version' => $targetVersion,
                'error' => $testResult['error'],
            ], ['php_version' => $oldVersion]);

            return [
                'success' => false,
                'error' => "Nginx configuration test failed. Automated rollback executed: " . ($testResult['error_output'] ?: $testResult['error']),
            ];
        }

        // 6. Reload Nginx
        $reloadResult = $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
        if (!$reloadResult['success']) {
            // Automated Rollback
            $this->executeSudoCommand(['cp', $backupPath, $vhostPath]);
            $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);

            return [
                'success' => false,
                'error' => "Failed to reload Nginx: " . ($reloadResult['error_output'] ?: $reloadResult['error']),
            ];
        }

        // 7. Update Database Model
        $website->update(['php_version' => $targetVersion]);
        if ($website->subscription) {
            $website->subscription->update(['php_version' => $targetVersion]);
        }

        // 8. Log Audit Activity
        $this->logAction($adminId, 'website_php_switched', [
            'domain' => $domain,
            'new_version' => $targetVersion,
            'socket' => $targetSocket,
        ], ['php_version' => $oldVersion]);

        return [
            'success' => true,
            'domain' => $domain,
            'old_version' => $oldVersion,
            'new_version' => $targetVersion,
            'message' => "Website {$domain} successfully switched from PHP {$oldVersion} to PHP {$targetVersion}.",
        ];
    }

    /**
     * Flush OPcache for a specific PHP version by safely reloading its FPM service.
     */
    public function flushOpcache(string $version, ?int $adminId = null): array
    {
        $res = $this->fpmService->manageService($version, 'reload');

        if ($res['success']) {
            $this->logAction($adminId, 'opcache_flushed', [
                'version' => $version,
                'method' => 'fpm_graceful_reload',
            ], []);

            return [
                'success' => true,
                'version' => $version,
                'message' => "OPcache for PHP {$version} flushed successfully (FPM graceful reload).",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to flush OPcache: " . $res['error'],
        ];
    }

    /**
     * Set Server Default PHP Version.
     */
    public function setDefaultPhpVersion(string $version, ?int $adminId = null): array
    {
        $installed = $this->discoveryService->discoverInstalledVersions();
        if (!isset($installed[$version])) {
            return ['success' => false, 'error' => "PHP {$version} is not installed on this server."];
        }

        $oldDefault = SystemSetting::get('default_php_version', '8.2');
        SystemSetting::set('default_php_version', $version);

        $this->logAction($adminId, 'default_php_version_changed', [
            'new_default' => $version,
        ], ['old_default' => $oldDefault]);

        return [
            'success' => true,
            'message' => "Server Default PHP version updated to PHP {$version}.",
            'default_version' => $version,
        ];
    }

    /**
     * Helper to log audit activity.
     */
    protected function logAction(?int $adminId, string $action, array $newValues, array $oldValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PHP Manager: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }
    }
}
