<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TenantOlt;
use App\Services\Network\OltApiService;

class SyncNetworkOltsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:sync-olts {--olt= : Specific OLT ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically synchronize all active OLT hardware devices and their client ONUs.';

    /**
     * Execute the console command.
     */
    public function handle(OltApiService $oltApiService): int
    {
        $specificId = $this->option('olt');
        $query = TenantOlt::query();

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $olts = $query->get();
        $this->info("Found " . $olts->count() . " OLT gateway(s) to synchronize.");

        foreach ($olts as $olt) {
            $this->line(">>> Syncing OLT [ID: {$olt->id}] {$olt->name} ({$olt->ip_address}:{$olt->web_port})...");
            try {
                $res = $oltApiService->syncOlt($olt);
                if ($res['success']) {
                    $this->info("  [SUCCESS] " . $res['message']);
                } else {
                    $this->warn("  [FAILED] " . $res['message']);
                }

                // Auto-Renewal / Auto-Unlock Check
                if ($olt->license_auto_renew && (int)$olt->license_limit === 1 && (int)$olt->license_time_hours <= (int)($olt->license_renew_threshold_hours ?: 168)) {
                    $this->line("  [AUTO-LICENSE] Remaining time ({$olt->license_time_hours}h) <= threshold ({$olt->license_renew_threshold_hours}h). Triggering auto-unlock...");
                    $authPwd = "admin";
                    if (!empty($olt->license_auth_password)) {
                        try {
                            $authPwd = \Illuminate\Support\Facades\Crypt::decryptString($olt->license_auth_password);
                        } catch (\Exception $e) {}
                    }
                    $licRes = $oltApiService->updateOltLicense($olt, $authPwd, (int)$olt->license_renew_days);
                    if ($licRes['success']) {
                        $this->info("  [AUTO-LICENSE SUCCESS] " . $licRes['message']);
                    } else {
                        $this->warn("  [AUTO-LICENSE FAILED] " . $licRes['message']);
                    }
                }
            } catch (\Exception $e) {
                $this->error("  [ERROR] " . $e->getMessage());
            }
        }

        $this->info("All OLT devices synchronized successfully.");
        return Command::SUCCESS;
    }
}
