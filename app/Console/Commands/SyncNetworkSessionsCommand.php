<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\TenantCustomer;
use App\Services\Network\MikrotikApiService;
use Illuminate\Support\Facades\DB;

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
    public function handle(MikrotikApiService $mikrotikApi): int
    {
        $tenantId = $this->option('tenant');
        $tenantsQuery = Tenant::query();
        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }
        $tenants = $tenantsQuery->get();

        foreach ($tenants as $tenant) {
            $routers = TenantRouter::where('tenant_id', $tenant->id)->where('is_active', true)->get();
            if ($routers->isEmpty()) {
                $routers = TenantRouter::where('tenant_id', $tenant->id)->get();
            }

            if ($routers->isEmpty()) {
                continue;
            }

            $activeUsernames = [];

            foreach ($routers as $router) {
                try {
                    $res = $mikrotikApi->getActivePppoe($router);
                    if (!empty($res['success']) && !empty($res['data'])) {
                        foreach ($res['data'] as $session) {
                            $u = trim((string)($session['name'] ?? ''));
                            if ($u !== '') {
                                $activeUsernames[$u] = true;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn("Router [{$router->name}] failed to poll active PPPoE: " . $e->getMessage());
                }
            }

            // Also check FreeRADIUS radacct if active
            try {
                $radiusSessions = DB::table('radacct')
                    ->where('tenant_id', $tenant->id)
                    ->whereNull('acctstoptime')
                    ->pluck('username');

                foreach ($radiusSessions as $ru) {
                    $ru = trim((string)$ru);
                    if ($ru !== '') {
                        $activeUsernames[$ru] = true;
                    }
                }
            } catch (\Exception $e) {}

            $onlineList = array_values(array_map('strval', array_keys($activeUsernames)));
            $onlineCount = count($onlineList);

            if (!empty($onlineList)) {
                foreach (array_chunk($onlineList, 500) as $chunk) {
                    TenantCustomer::where('tenant_id', $tenant->id)
                        ->whereIn('username', $chunk)
                        ->update(['online_status' => 'online']);
                }

                TenantCustomer::where('tenant_id', $tenant->id)
                    ->whereNotIn('username', $onlineList)
                    ->update(['online_status' => 'offline']);
            } else {
                TenantCustomer::where('tenant_id', $tenant->id)
                    ->update(['online_status' => 'offline']);
            }

            $this->info("Tenant [{$tenant->id}] synced: {$onlineCount} active sessions online.");
        }

        return Command::SUCCESS;
    }
}
