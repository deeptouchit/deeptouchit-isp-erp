<?php

namespace App\Console\Commands;

use App\Services\PHP\PhpDiscoveryService;
use App\Services\PHP\PhpFpmService;
use Illuminate\Console\Command;

class PhpHealthCheckCommand extends Command
{
    protected $signature = 'php:health-check';
    protected $description = 'Perform deep health inspection on all PHP-FPM services, sockets, and configs';

    public function handle(PhpDiscoveryService $discovery, PhpFpmService $fpm): int
    {
        $this->info("Running Multi-PHP Engine Health Inspection...");
        $versions = $discovery->discoverInstalledVersions();

        $rows = [];
        $hasErrors = false;

        foreach ($versions as $v => $info) {
            $syntaxTest = $fpm->validateConfiguration($v);
            $isHealthy = $info['is_healthy'] && $syntaxTest['success'];

            if (!$isHealthy) {
                $hasErrors = true;
            }

            $rows[] = [
                'PHP ' . $v,
                $info['fpm_service'],
                $info['fpm_status'] === 'running' ? '✓ Running' : '✗ Stopped',
                $info['socket_exists'] ? '✓ Online' : '✗ Missing',
                $syntaxTest['success'] ? '✓ Valid' : '✗ Syntax Error',
                $info['fpm_details']['workers'] . ' Workers',
                $info['fpm_details']['memory_formatted'],
                $isHealthy ? '● HEALTHY' : '⚠ ATTENTION',
            ];
        }

        $this->table(['Version', 'Service', 'Daemon', 'Socket', 'Config Syntax', 'Workers', 'RAM', 'Overall Health'], $rows);

        if ($hasErrors) {
            $this->warn("Health inspection completed with warnings.");
            return 1;
        }

        $this->info("All Multi-PHP engines are 100% HEALTHY and operational.");
        return 0;
    }
}
