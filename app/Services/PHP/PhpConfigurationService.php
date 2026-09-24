<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpConfigurationService
{
    use CommandExecutor;

    protected PhpFpmService $fpmService;
    protected string $backupDir;

    /**
     * Whitelist of safe editable directives with default values and validation rules.
     */
    protected array $supportedDirectives = [
        // Resource Limits
        'memory_limit' => ['type' => 'size', 'default' => '512M', 'options' => ['128M', '256M', '512M', '1024M', '2048M', '-1']],
        'upload_max_filesize' => ['type' => 'size', 'default' => '256M', 'options' => ['32M', '64M', '128M', '256M', '512M', '1024M']],
        'post_max_size' => ['type' => 'size', 'default' => '256M', 'options' => ['32M', '64M', '128M', '256M', '512M', '1024M']],
        'max_execution_time' => ['type' => 'int', 'default' => '300', 'options' => ['30', '60', '120', '300', '600', '1200']],
        'max_input_time' => ['type' => 'int', 'default' => '300', 'options' => ['60', '120', '300', '600']],
        'max_input_vars' => ['type' => 'int', 'default' => '5000', 'options' => ['1000', '2000', '3000', '5000', '10000']],
        'max_file_uploads' => ['type' => 'int', 'default' => '20', 'options' => ['10', '20', '50', '100']],

        // Error Handling
        'display_errors' => ['type' => 'bool', 'default' => 'Off', 'options' => ['Off', 'On']],
        'display_startup_errors' => ['type' => 'bool', 'default' => 'Off', 'options' => ['Off', 'On']],
        'log_errors' => ['type' => 'bool', 'default' => 'On', 'options' => ['On', 'Off']],
        'error_reporting' => ['type' => 'string', 'default' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT'],

        // Security
        'allow_url_fopen' => ['type' => 'bool', 'default' => 'On', 'options' => ['On', 'Off']],
        'allow_url_include' => ['type' => 'bool', 'default' => 'Off', 'options' => ['Off', 'On']],
        'expose_php' => ['type' => 'bool', 'default' => 'Off', 'options' => ['Off', 'On']],
        'disable_functions' => ['type' => 'string', 'default' => 'exec,passthru,shell_exec,system,proc_open,popen,curl_multi_exec,parse_ini_file,show_source'],

        // Sessions
        'session.gc_maxlifetime' => ['type' => 'int', 'default' => '1440', 'options' => ['1440', '3600', '7200', '14400', '86400']],
        'session.cookie_secure' => ['type' => 'bool', 'default' => 'Off', 'options' => ['Off', 'On']],
        'session.cookie_httponly' => ['type' => 'bool', 'default' => 'On', 'options' => ['On', 'Off']],
        'session.cookie_samesite' => ['type' => 'string', 'default' => 'Lax', 'options' => ['Lax', 'Strict', 'None']],

        // Performance / Caching
        'realpath_cache_size' => ['type' => 'size', 'default' => '4096K', 'options' => ['2048K', '4096K', '8192K', '16384K']],
        'realpath_cache_ttl' => ['type' => 'int', 'default' => '120', 'options' => ['60', '120', '300', '600']],
        'opcache.enable' => ['type' => 'bool', 'default' => '1', 'options' => ['1', '0']],
        'opcache.memory_consumption' => ['type' => 'int', 'default' => '128', 'options' => ['64', '128', '256', '512']],
    ];

    public function __construct(PhpFpmService $fpmService)
    {
        $this->fpmService = $fpmService;
        $this->backupDir = storage_path('app/php-backups');
    }

    /**
     * Get list of supported directives metadata.
     */
    public function getSupportedDirectives(): array
    {
        return $this->supportedDirectives;
    }

    /**
     * Read current effective directives for a specific PHP version.
     */
    public function getDirectives(string $version): array
    {
        $customIni = "/etc/php/{$version}/fpm/conf.d/99-deeptouchhost-custom.ini";
        $fpmIni = "/etc/php/{$version}/fpm/php.ini";

        $customValues = [];
        if (file_exists($customIni)) {
            $parsed = parse_ini_file($customIni, false, INI_SCANNER_RAW);
            if ($parsed !== false) {
                $customValues = $parsed;
            }
        }

        $fpmValues = [];
        if (file_exists($fpmIni)) {
            $parsedFpm = parse_ini_file($fpmIni, false, INI_SCANNER_RAW);
            if ($parsedFpm !== false) {
                $fpmValues = $parsedFpm;
            }
        }

        $result = [];
        foreach ($this->supportedDirectives as $key => $meta) {
            $currentVal = $customValues[$key] ?? $fpmValues[$key] ?? $meta['default'];
            
            // Normalize boolean display values
            if ($meta['type'] === 'bool') {
                if ($currentVal === '1' || $currentVal === 1 || strtolower((string)$currentVal) === 'on' || $currentVal === true) {
                    $currentVal = ($meta['options'][0] === '1') ? '1' : 'On';
                } else {
                    $currentVal = ($meta['options'][0] === '0') ? '0' : 'Off';
                }
            }

            $result[$key] = [
                'name' => $key,
                'value' => (string)$currentVal,
                'is_customized' => isset($customValues[$key]),
                'meta' => $meta,
            ];
        }

        return $result;
    }

    /**
     * Safely apply new directives with backup, validation, and automated rollback.
     */
    public function updateDirectives(string $version, array $newValues, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $confDir = "/etc/php/{$version}/fpm/conf.d";
        $targetFile = "{$confDir}/99-deeptouchhost-custom.ini";

        // Read old values for audit log
        $oldDirectives = $this->getDirectives($version);
        $oldValues = array_map(fn($item) => $item['value'], $oldDirectives);

        // 1. Validate & Sanitize new values
        $validatedSettings = [];
        foreach ($newValues as $key => $val) {
            if (!isset($this->supportedDirectives[$key])) continue;

            $meta = $this->supportedDirectives[$key];
            $valStr = trim((string)$val);

            // Validation per type
            if ($meta['type'] === 'size' && !preg_match('/^-?[0-9]+[KMGkmg]?$/', $valStr)) {
                return ['success' => false, 'error' => "Invalid size format for {$key}: {$valStr}"];
            } elseif ($meta['type'] === 'int' && !is_numeric($valStr)) {
                return ['success' => false, 'error' => "Invalid integer format for {$key}: {$valStr}"];
            }

            $validatedSettings[$key] = $valStr;
        }

        if (empty($validatedSettings)) {
            return ['success' => false, 'error' => 'No valid settings provided.'];
        }

        // 2. Create timestamped backup
        $backupPath = $this->createBackup($version, $targetFile);

        // 3. Generate new INI file content
        $content = "; ====================================================================\n";
        $content .= "; DeepTouch Host DeepTouchHost Custom PHP {$version} FPM Configuration\n";
        $content .= "; Generated at: " . date('Y-m-d H:i:s') . "\n";
        $content .= "; ====================================================================\n\n";

        foreach ($validatedSettings as $key => $val) {
            $content .= "{$key} = \"{$val}\"\n";
        }

        // 4. Write to temp file
        $tempPath = storage_path("app/temp_php_{$version}_" . uniqid() . ".ini");
        File::put($tempPath, $content);

        // 5. Safely copy to target conf.d location
        $copyRes = $this->executeSudoCommand(['cp', $tempPath, $targetFile]);
        @unlink($tempPath);

        if (!$copyRes['success']) {
            return ['success' => false, 'error' => 'Failed to write configuration: ' . $copyRes['error']];
        }

        $this->executeSudoCommand(['chmod', '644', $targetFile]);

        // 6. Test syntax via php-fpm -t
        $syntaxTest = $this->fpmService->validateConfiguration($version);
        if (!$syntaxTest['success']) {
            Log::warning("PHP {$version} config syntax test failed, triggering automated rollback: " . $syntaxTest['error']);
            $this->rollback($targetFile, $backupPath);
            $this->fpmService->manageService($version, 'reload');

            $this->logAction($adminId, "php_{$version}_config_failed", [
                'error' => $syntaxTest['error'],
                'attempted' => $validatedSettings,
            ], $oldValues);

            return [
                'success' => false,
                'error' => 'PHP Configuration syntax validation failed. Automated rollback executed successfully: ' . $syntaxTest['error'],
            ];
        }

        // 7. Reload PHP-FPM service
        $reloadRes = $this->fpmService->manageService($version, 'reload');
        if (!$reloadRes['success']) {
            Log::warning("PHP {$version} FPM reload failed, triggering automated rollback: " . $reloadRes['error']);
            $this->rollback($targetFile, $backupPath);
            $this->fpmService->manageService($version, 'restart');

            $this->logAction($adminId, "php_{$version}_reload_failed", [
                'error' => $reloadRes['error'],
                'attempted' => $validatedSettings,
            ], $oldValues);

            return [
                'success' => false,
                'error' => 'Service reload failed. Configuration was rolled back: ' . $reloadRes['error'],
            ];
        }

        // 8. Record audit log
        $this->logAction($adminId, "php_{$version}_config_updated", $validatedSettings, $oldValues);

        return [
            'success' => true,
            'version' => $version,
            'message' => "PHP {$version} configuration applied and service reloaded successfully.",
            'backup' => $backupPath,
        ];
    }

    /**
     * Reset custom configuration back to default system values.
     */
    public function resetToDefaults(string $version, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $targetFile = "/etc/php/{$version}/fpm/conf.d/99-deeptouchhost-custom.ini";
        if (file_exists($targetFile)) {
            $backupPath = $this->createBackup($version, $targetFile);
            $this->executeSudoCommand(['rm', '-f', $targetFile]);
            
            // Validate & reload
            $this->fpmService->manageService($version, 'reload');

            $this->logAction($adminId, "php_{$version}_config_reset", ['reset' => true], ['backup' => $backupPath]);

            return [
                'success' => true,
                'message' => "PHP {$version} configuration reset to system defaults successfully.",
            ];
        }

        return [
            'success' => true,
            'message' => "PHP {$version} is already using default system configuration.",
        ];
    }

    /**
     * Get raw php.ini and custom INI content.
     */
    public function getRawIni(string $version): array
    {
        $customFile = "/etc/php/{$version}/fpm/conf.d/99-deeptouchhost-custom.ini";
        $mainFile = "/etc/php/{$version}/fpm/php.ini";

        return [
            'custom_ini_path' => $customFile,
            'custom_ini_content' => file_exists($customFile) ? @file_get_contents($customFile) : '',
            'main_ini_path' => $mainFile,
            'main_ini_content' => file_exists($mainFile) ? @file_get_contents($mainFile) : '',
        ];
    }

    /**
     * Save raw custom INI content with validation and reload.
     */
    public function updateRawIni(string $version, string $rawContent, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $targetFile = "/etc/php/{$version}/fpm/conf.d/99-deeptouchhost-custom.ini";
        $backupPath = $this->createBackup($version, $targetFile);

        $tempPath = storage_path("app/temp_raw_php_{$version}_" . uniqid() . ".ini");
        File::put($tempPath, $rawContent);
        $this->executeSudoCommand(['cp', $tempPath, $targetFile]);
        @unlink($tempPath);

        $this->executeSudoCommand(['chmod', '644', $targetFile]);

        $syntaxTest = $this->fpmService->validateConfiguration($version);
        if (!$syntaxTest['success']) {
            $this->rollback($targetFile, $backupPath);
            $this->fpmService->manageService($version, 'reload');
            return [
                'success' => false,
                'error' => "Syntax error detected in raw INI, rolled back: " . $syntaxTest['error'],
            ];
        }

        $this->fpmService->manageService($version, 'reload');

        $this->logAction($adminId, "php_{$version}_raw_ini_updated", ['updated' => true], []);

        return [
            'success' => true,
            'message' => "PHP {$version} raw INI configuration saved and FastCGI daemon reloaded.",
        ];
    }

    /**
     * Create timestamped backup of current configuration.
     */
    protected function createBackup(string $version, string $sourceFile): ?string
    {
        $versionBackupDir = "{$this->backupDir}/{$version}";
        File::ensureDirectoryExists($versionBackupDir);

        $timestamp = date('Y_m_d_His');
        $dest = "{$versionBackupDir}/backup_{$timestamp}.ini";

        if (file_exists($sourceFile)) {
            $this->executeSudoCommand(['cp', $sourceFile, $dest]);
            return $dest;
        }

        return null;
    }

    /**
     * Restore previous backup on failure.
     */
    protected function rollback(string $targetFile, ?string $backupPath): void
    {
        if ($backupPath && file_exists($backupPath)) {
            $this->executeSudoCommand(['cp', $backupPath, $targetFile]);
        } else {
            $this->executeSudoCommand(['rm', '-f', $targetFile]);
        }
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
                'description' => "PHP Configuration update for {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log PHP config activity: " . $e->getMessage());
        }
    }
}
