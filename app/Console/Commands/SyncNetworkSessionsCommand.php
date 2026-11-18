<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\Network\CustomerSessionStateService;

class SyncNetworkSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:sync-sessions {--tenant= : Specific Tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically synchronize active PPPoE sessions and MikroTik secret states for all tenant routers.';

    /**
     * Execute the console command.
     */
    public function handle(CustomerSessionStateService $sessionState): int
    {
        $tenantId = $this->option('tenant');
        $tenantsQuery = Tenant::query();
        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }
        $tenants = $tenantsQuery->get();

        foreach ($tenants as $tenant) {
            $result = $sessionState->syncTenant($tenant);
            if (!$result['success']) {
                $this->warn("Tenant [{$tenant->id}] session sources failed; existing states preserved.");
                continue;
            }

            $mode = $result['degraded'] ? 'degraded' : 'healthy';
            $this->info("Tenant [{$tenant->id}] synced ({$mode}): {$result['active_count']} active sessions online.");
        }

        return Command::SUCCESS;
    }
}

