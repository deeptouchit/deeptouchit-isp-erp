<?php

namespace App\Services\Network;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantRouter;
use Illuminate\Support\Facades\DB;

class CustomerSessionStateService
{
    public function __construct(private readonly MikrotikApiService $mikrotikApi)
    {
    }

    /**
     * Synchronize authoritative session sources without destroying the last known
     * state when every source is unavailable.
     *
     * @return array{success: bool, degraded: bool, status: string, active_count: int, online_usernames: array, successful_sources: int, failed_sources: int, errors: array}
     */
    public function syncTenant(Tenant $tenant): array
    {
        $routers = TenantRouter::where('tenant_id', $tenant->id)->where('is_active', true)->get();
        if ($routers->isEmpty()) {
            $routers = TenantRouter::where('tenant_id', $tenant->id)->get();
        }

        $activeUsernames = [];
        $successfulSources = 0;
        $failedSources = 0;
        $errors = [];

        foreach ($routers as $router) {
            try {
                $result = $this->mikrotikApi->getActivePppoe($router);
                if (empty($result['success'])) {
                    $failedSources++;
                    $errors[] = "MikroTik {$router->name}: " . ($result['message'] ?? 'poll failed');
                    continue;
                }

                $successfulSources++;
                foreach (($result['data'] ?? []) as $session) {
                    $username = trim((string) ($session['name'] ?? ''));
                    if ($username !== '') {
                        $activeUsernames[$username] = true;
                    }
                }
            } catch (\Throwable $error) {
                $failedSources++;
                $errors[] = "MikroTik {$router->name}: {$error->getMessage()}";
            }
        }

        try {
            $radiusSessions = DB::table('radacct')
                ->where('tenant_id', $tenant->id)
                ->whereNull('acctstoptime')
                ->pluck('username');
            $successfulSources++;
            foreach ($radiusSessions as $username) {
                $username = trim((string) $username);
                if ($username !== '') {
                    $activeUsernames[$username] = true;
                }
            }
        } catch (\Throwable $error) {
            $failedSources++;
            $errors[] = 'FreeRADIUS: ' . $error->getMessage();
        }

        $onlineList = array_values(array_keys($activeUsernames));
        if ($successfulSources === 0) {
            return [
                'success' => false,
                'degraded' => true,
                'status' => 'failed',
                'active_count' => 0,
                'online_usernames' => [],
                'successful_sources' => 0,
                'failed_sources' => $failedSources,
                'errors' => $errors,
            ];
        }

        $syncedAt = now();
        TenantCustomer::where('tenant_id', $tenant->id)->update([
            'online_status' => 'offline',
            'last_sync_at' => $syncedAt,
        ]);
        foreach (array_chunk($onlineList, 500) as $chunk) {
            TenantCustomer::where('tenant_id', $tenant->id)
                ->whereIn('username', $chunk)
                ->update([
                    'online_status' => 'online',
                    'last_online_at' => $syncedAt,
                    'last_sync_at' => $syncedAt,
                ]);
        }

        return [
            'success' => true,
            'degraded' => $failedSources > 0,
            'status' => $failedSources > 0 ? 'degraded' : 'healthy',
            'active_count' => count($onlineList),
            'online_usernames' => $onlineList,
            'successful_sources' => $successfulSources,
            'failed_sources' => $failedSources,
            'errors' => $errors,
        ];
    }
}
