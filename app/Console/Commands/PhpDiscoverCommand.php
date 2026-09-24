<?php

namespace App\Console\Commands;

use App\Services\PHP\PhpDiscoveryService;
use Illuminate\Console\Command;

class PhpDiscoverCommand extends Command
{
    protected $signature = 'php:discover';
    protected $description = 'Dynamically scan and discover all installed PHP versions, FPM services, and sockets';

    public function handle(PhpDiscoveryService $discovery): int
    {
        $this->info("Scanning server for installed Multi-PHP versions...");
        $versions = $discovery->discoverInstalledVersions();

        if (empty($versions)) {
            $this->error("No PHP versions discovered.");
            return 1;
        }

        $rows = [];
        foreach ($versions as $v => $info) {
            $rows[] = [
                'PHP ' . $v,
                $info['fpm_service'],
                $info['fpm_status'],
                $info['fpm_socket'],
                $info['socket_exists'] ? '✓ Ready' : '✗ Missing',
                count($info['extensions']) . ' Modules',
                $info['opcache_enabled'] ? '✓ Active' : '✗ Disabled',
            ];
        }

        $this->table(['Version', 'Service', 'Status', 'Socket File', 'Socket Ready', 'Extensions', 'OPcache'], $rows);
        $this->info("Discovery completed successfully. Found " . count($versions) . " active PHP engines.");

        return 0;
    }
}
