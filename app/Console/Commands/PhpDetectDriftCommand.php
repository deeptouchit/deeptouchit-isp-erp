<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\PHP\PhpManagerService;
use App\Services\PHP\PhpStateSyncService;
use Illuminate\Console\Command;

class PhpDetectDriftCommand extends Command
{
    protected $signature = 'php:detect-drift {--repair : Automatically repair detected configuration drift}';
    protected $description = 'Scan all hosted websites for drift between Database expected PHP and actual Nginx FastCGI configuration';

    public function handle(PhpStateSyncService $syncService, PhpManagerService $phpManager): int
    {
        $this->info("Scanning hosted websites for configuration drift...");
        $driftReports = $syncService->detectDrift();

        if (empty($driftReports)) {
            $this->info("✓ Zero configuration drift detected. All hosted websites match server VHost FastCGI sockets.");
            return 0;
        }

        $this->warn("Detected " . count($driftReports) . " configuration drift item(s):");
        $rows = [];
        foreach ($driftReports as $d) {
            $rows[] = [
                $d['domain'],
                'PHP ' . $d['expected_version'],
                $d['actual_version'] ? 'PHP ' . $d['actual_version'] : 'None',
                $d['actual_socket'],
                $d['reason'],
            ];
        }

        $this->table(['Domain', 'Expected (DB)', 'Actual (Nginx)', 'Actual Socket', 'Drift Reason'], $rows);

        if ($this->option('repair')) {
            $this->info("Auto-repairing drift for detected websites...");
            foreach ($driftReports as $d) {
                $site = Website::find($d['website_id']);
                if ($site) {
                    $res = $phpManager->switchWebsitePhpVersion($site, $d['expected_version']);
                    if ($res['success']) {
                        $this->info("✓ Repaired {$d['domain']} -> PHP {$d['expected_version']}");
                    } else {
                        $this->error("✗ Failed to repair {$d['domain']}: " . $res['error']);
                    }
                }
            }
        } else {
            $this->comment("Run with --repair to automatically synchronize Nginx VHost configs with expected database versions.");
        }

        return 0;
    }
}
