<?php

namespace App\Services\DNS;

use App\Models\ActivityLog;
use App\Models\DnsZone;
use App\Models\Nameserver;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class NameserverService
{
    use CommandExecutor;

    protected DnsZoneService $zoneService;

    public function __construct(DnsZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * Ensure default authoritative nameservers exist.
     */
    public function ensureDefaultNameservers(?string $defaultIp = null): void
    {
        $defaultIp = $defaultIp ?: \App\Support\ServerHelper::getPublicIp();

        if (Nameserver::count() > 0) {
            return;
        }

        Nameserver::create([
            'hostname' => 'ns1.deeptouchit.com',
            'ip_address' => $defaultIp,
            'is_primary' => true,
            'is_default' => true,
            'status' => 'active',
            'check_status' => 'online',
            'last_checked_at' => now(),
            'response_time_ms' => 1,
        ]);

        Nameserver::create([
            'hostname' => 'ns2.deeptouchit.com',
            'ip_address' => $defaultIp,
            'is_primary' => false,
            'is_default' => true,
            'status' => 'active',
            'check_status' => 'online',
            'last_checked_at' => now(),
            'response_time_ms' => 1,
        ]);
    }

    /**
     * Create custom nameserver.
     */
    public function createNameserver(array $data, ?int $adminId = null): Nameserver
    {
        $hostname = strtolower(trim($data['hostname']));
        $ip = trim($data['ip_address']);

        $ns = Nameserver::create([
            'hostname' => $hostname,
            'ip_address' => $ip,
            'ipv6_address' => $data['ipv6_address'] ?? null,
            'is_primary' => !empty($data['is_primary']),
            'is_default' => !empty($data['is_default']),
            'status' => 'active',
        ]);

        $this->testNameserver($ns);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'nameserver_created',
            'description' => "Added nameserver `{$hostname}` pointing to `{$ip}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['hostname' => $hostname, 'ip_address' => $ip],
        ]);

        return $ns;
    }

    /**
     * Update nameserver node.
     */
    public function updateNameserver(Nameserver $nameserver, array $data, ?int $adminId = null): Nameserver
    {
        $old = $nameserver->toArray();

        $nameserver->update([
            'hostname' => strtolower(trim($data['hostname'] ?? $nameserver->hostname)),
            'ip_address' => trim($data['ip_address'] ?? $nameserver->ip_address),
            'ipv6_address' => $data['ipv6_address'] ?? $nameserver->ipv6_address,
            'is_primary' => isset($data['is_primary']) ? (bool)$data['is_primary'] : $nameserver->is_primary,
            'is_default' => isset($data['is_default']) ? (bool)$data['is_default'] : $nameserver->is_default,
        ]);

        $this->testNameserver($nameserver);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'nameserver_updated',
            'description' => "Updated nameserver `{$nameserver->hostname}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $old,
            'new_values' => $nameserver->fresh()->toArray(),
        ]);

        return $nameserver;
    }

    /**
     * Test Port 53 socket connectivity and latency.
     */
    public function testNameserver(Nameserver $nameserver): array
    {
        $ip = $nameserver->ip_address;
        $port = 53;
        $timeout = 2.0;

        $startTime = microtime(true);
        $status = 'offline';
        $latency = null;

        $serverPublicIp = \App\Support\ServerHelper::getPublicIp();
        $targetIp = ($ip === $serverPublicIp || $ip === '127.0.0.1' || \App\Support\ServerHelper::isPrivateIp($ip)) ? '127.0.0.1' : $ip;

        $socket = @fsockopen("udp://{$targetIp}", $port, $errno, $errstr, $timeout);
        if ($socket) {
            $latency = (int)(round(microtime(true) - $startTime, 3) * 1000);
            $latency = max($latency, 1);
            $status = 'online';
            fclose($socket);
        }

        $nameserver->update([
            'check_status' => $status,
            'response_time_ms' => $latency,
            'last_checked_at' => now(),
        ]);

        return [
            'status' => $status,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Test all configured nameservers.
     */
    public function testAllNameservers(): void
    {
        $all = Nameserver::all();
        foreach ($all as $ns) {
            $this->testNameserver($ns);
        }
    }

    /**
     * Bulk synchronize primary & secondary nameservers to all hosted zones.
     */
    public function syncToAllZones(string $primaryNs, string $secondaryNs): array
    {
        $zones = DnsZone::all();
        $count = 0;

        foreach ($zones as $zone) {
            $zone->update([
                'primary_ns' => $primaryNs,
                'secondary_ns' => $secondaryNs,
            ]);

            // Update NS records in zone
            $zone->records()->where('type', 'NS')->delete();
            $zone->records()->create([
                'name' => '@',
                'type' => 'NS',
                'content' => rtrim($primaryNs, '.') . '.',
                'ttl' => 86400,
                'status' => 'active',
            ]);
            $zone->records()->create([
                'name' => '@',
                'type' => 'NS',
                'content' => rtrim($secondaryNs, '.') . '.',
                'ttl' => 86400,
                'status' => 'active',
            ]);

            // Recompile BIND zone
            $this->zoneService->incrementSerial($zone);
            $count++;
        }

        $this->zoneService->reloadBind();

        return [
            'success' => true,
            'message' => "Synchronized nameservers ({$primaryNs}, {$secondaryNs}) across all {$count} DNS zones.",
        ];
    }

    /**
     * Delete nameserver node.
     */
    public function deleteNameserver(Nameserver $nameserver): array
    {
        if ($nameserver->is_primary) {
            return ['success' => false, 'error' => 'The primary authoritative nameserver cannot be deleted.'];
        }

        $hostname = $nameserver->hostname;
        $nameserver->delete();

        return ['success' => true, 'message' => "Nameserver `{$hostname}` deleted successfully."];
    }
}
