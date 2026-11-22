<?php

namespace App\Services\Network;

use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use App\Models\TenantRouter;
use Illuminate\Support\Facades\Cache;

class ResellerSessionTraffic
{
    public function __construct(private readonly MikrotikApiService $api) {}

    public function measure(TenantReseller $reseller): array
    {
        $customers = TenantCustomer::where('tenant_id', $reseller->tenant_id)
            ->where('reseller_id', $reseller->id)
            ->get(['username', 'connection_type', 'router_id']);
        $pppoe = $customers->filter(fn ($customer) => in_array($customer->connection_type, ['pppoe', null], true));
        $usernames = $pppoe->pluck('username')->flip();
        if ($customers->isEmpty()) {
            return ['state' => 'live', 'sessions' => 0, 'point' => $this->point(0, 0)];
        }
        if ($pppoe->isEmpty()) {
            return ['state' => 'unsupported', 'sessions' => null, 'point' => null];
        }

        $routers = TenantRouter::where('tenant_id', $reseller->tenant_id)
            ->where('is_active', true)
            ->whereIn('connection_type', ['api', 'hybrid'])
            ->get();
        if ($routers->isEmpty()) {
            return ['state' => 'unconfigured', 'sessions' => null, 'point' => null];
        }

        $routerIds = $routers->modelKeys();
        $incomplete = $pppoe->count() !== $customers->count()
            || $pppoe->contains(fn ($customer) => $customer->router_id && !in_array((int) $customer->router_id, $routerIds, true));
        $available = 0;
        $pending = 0;
        $measured = 0;
        $active = 0;
        $download = 0.0;
        $upload = 0.0;
        $observedAt = null;

        foreach ($routers as $router) {
            $snapshot = $this->routerSnapshot($router);
            if ($snapshot === null) {
                $incomplete = true;
                continue;
            }

            $available++;
            $observedAt = min($observedAt ?? $snapshot['checked_at'], $snapshot['checked_at']);
            foreach ($snapshot['sessions'] as $id => $session) {
                if (!$usernames->has($session['username'])) {
                    continue;
                }
                $active++;
                $prior = $snapshot['previous'][$id] ?? null;
                $seconds = $snapshot['elapsed'];
                if (!$prior || $prior['username'] !== $session['username'] || $seconds < 1 || $seconds > 60
                    || $session['download'] < $prior['download'] || $session['upload'] < $prior['upload']) {
                    $pending++;
                    continue;
                }

                $measured++;
                $download += ($session['download'] - $prior['download']) * 8 / $seconds / 1000000;
                $upload += ($session['upload'] - $prior['upload']) * 8 / $seconds / 1000000;
            }
        }

        if ($available === 0) {
            return ['state' => 'unavailable', 'sessions' => null, 'point' => null];
        }
        if ($pending > 0 && $measured === 0) {
            return ['state' => 'sampling', 'sessions' => $active, 'point' => null];
        }

        return [
            'state' => $incomplete || $pending > 0 ? 'partial' : 'live',
            'sessions' => $active,
            'point' => $active === 0 && $incomplete ? null : $this->point($download, $upload, $observedAt),
        ];
    }

    private function point(float $download, float $upload, ?float $observedAt = null): array
    {
        return [
            'download' => round($download, 2),
            'upload' => round($upload, 2),
            'timestamp' => ($observedAt === null ? now() : now()->setTimestamp((int) $observedAt))->toIso8601String(),
        ];
    }

    private function routerSnapshot(TenantRouter $router): ?array
    {
        $key = "reseller-pppoe-router:{$router->tenant_id}:{$router->id}";
        $cached = Cache::get($key, []);
        if (($cached['checked_at'] ?? 0) > microtime(true) - 5) {
            return isset($cached['sessions']) ? $cached : null;
        }

        $snapshot = Cache::lock("$key:lock", 25)->get(function () use ($key, $router) {
            $prior = Cache::get($key, []);
            if (($prior['checked_at'] ?? 0) > microtime(true) - 5) {
                return $prior;
            }

            try {
                $sessions = $this->api->getPppoeSessionBytes($router);
            } catch (\Throwable $exception) {
                report($exception);
                $sessions = null;
            }

            $now = microtime(true);
            $snapshot = [
                'checked_at' => $now,
                'sessions' => $sessions,
                'previous' => $sessions === null ? [] : ($prior['sessions'] ?? []),
                'elapsed' => $now - ($prior['checked_at'] ?? 0),
            ];
            Cache::put($key, $snapshot, 120);

            return $snapshot;
        });

        return isset($snapshot['sessions']) ? $snapshot : null;
    }
}
