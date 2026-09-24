<?php

namespace App\Services\PHP;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class PhpFpmService
{
    use CommandExecutor;

    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Safely execute a systemd action on a PHP-FPM service.
     */
    public function manageService(string $version, string $action): array
    {
        // 1. Strict input validation
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return [
                'success' => false,
                'error' => "Invalid PHP version format: {$version}",
            ];
        }

        $allowedActions = ['start', 'stop', 'restart', 'reload'];
        if (!in_array($action, $allowedActions, true)) {
            return [
                'success' => false,
                'error' => "Invalid service action: {$action}. Allowed: " . implode(', ', $allowedActions),
            ];
        }

        $serviceName = "php{$version}-fpm";

        // 2. If reloading or restarting, first test configuration syntax
        if (in_array($action, ['reload', 'restart', 'start'], true)) {
            $testResult = $this->validateConfiguration($version);
            if (!$testResult['success']) {
                return [
                    'success' => false,
                    'error' => "Cannot {$action} {$serviceName}: PHP-FPM configuration syntax error - " . $testResult['error'],
                ];
            }
        }

        // 3. Execute whitelisted systemctl command
        $result = $this->executeSudoCommand(['systemctl', $action, $serviceName]);

        if (!$result['success']) {
            Log::error("Failed to {$action} {$serviceName}", $result);
            return [
                'success' => false,
                'error' => "Failed to {$action} {$serviceName}: " . ($result['error_output'] ?: $result['error']),
            ];
        }

        // 4. Verify post-action status
        usleep(200000); // 200ms grace period for systemd transition
        $status = $this->discoveryService->getFpmServiceStatus($serviceName);

        $isExpected = ($action === 'stop') 
            ? in_array($status['status'], ['stopped', 'inactive', 'failed']) 
            : ($status['status'] === 'running');

        return [
            'success' => true,
            'action' => $action,
            'service' => $serviceName,
            'version' => $version,
            'status' => $status['status'],
            'is_expected' => $isExpected,
            'details' => $status,
            'message' => "PHP {$version} FPM service {$action}ed successfully.",
        ];
    }

    /**
     * Validate PHP-FPM configuration syntax using `php-fpm -t`.
     */
    public function validateConfiguration(string $version): array
    {
        $fpmBinary = "/usr/sbin/php-fpm{$version}";
        
        // If specific binary doesn't exist, check default php-fpm
        if (!file_exists($fpmBinary)) {
            $fpmBinary = 'php-fpm' . $version;
        }

        $res = $this->executeSudoCommand([$fpmBinary, '-t']);

        if (!$res['success']) {
            $error = $res['error_output'] ?: $res['output'] ?: 'Configuration test failed.';
            return [
                'success' => false,
                'error' => trim($error),
            ];
        }

        return [
            'success' => true,
            'output' => trim($res['output'] . ' ' . $res['error_output']),
        ];
    }
}
