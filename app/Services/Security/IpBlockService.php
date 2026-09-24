<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Models\IpBlock;
use App\Traits\CommandExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IpBlockService
{
    use CommandExecutor;

    /**
     * Block a single IP address or CIDR subnet.
     */
    public function blockIp(array $data, ?int $adminId = null): array
    {
        $rawIp = trim($data['ip_address']);
        $reason = !empty($data['reason']) ? trim($data['reason']) : 'Manual Admin Security Block';
        $duration = $data['duration'] ?? 'permanent';
        $type = $data['type'] ?? 'user';
        $subscriptionId = $data['subscription_id'] ?? null;

        $isSubnet = str_contains($rawIp, '/');

        // Apply Kernel-level UFW Deny Rule at Priority 1
        try {
            $this->executeSudoCommand(['ufw', 'insert', '1', 'deny', 'from', $rawIp, 'to', 'any']);
        } catch (\Throwable $e) {
            Log::warning("UFW block failed for {$rawIp}: " . $e->getMessage());
        }

        $expiresAt = $this->calculateExpiry($duration);

        $block = IpBlock::updateOrCreate(
            ['ip_address' => $rawIp],
            [
                'subscription_id' => $subscriptionId,
                'is_subnet' => $isSubnet,
                'reason' => $reason,
                'type' => $type,
                'expires_at' => $expiresAt,
                'status' => 'active',
                'created_by' => $adminId ?: auth()->id() ?: 1,
            ]
        );

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_blocked',
            'description' => "Blocked IP address/subnet `{$rawIp}` at kernel firewall level. Reason: {$reason}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['ip' => $rawIp, 'reason' => $reason, 'expires_at' => $expiresAt?->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "IP / Subnet `{$rawIp}` has been blocked and added to kernel packet filter.",
            'block' => $block,
        ];
    }

    /**
     * Bulk block multiple IPs or CIDR subnets.
     */
    public function bulkBlock(array $data, ?int $adminId = null): array
    {
        $rawText = $data['ip_list'] ?? '';
        $reason = !empty($data['reason']) ? trim($data['reason']) : 'Bulk Threat Mitigation Block';
        $duration = $data['duration'] ?? 'permanent';

        $lines = preg_split('/[\r\n,]+/', $rawText);
        $blockedCount = 0;

        foreach ($lines as $line) {
            $ip = trim($line);
            if (empty($ip)) {
                continue;
            }

            $this->blockIp([
                'ip_address' => $ip,
                'reason' => $reason,
                'duration' => $duration,
                'type' => 'user',
            ], $adminId);

            $blockedCount++;
        }

        return [
            'success' => true,
            'message' => "Bulk mitigation complete. Successfully blocked {$blockedCount} IP addresses/subnets.",
        ];
    }

    /**
     * Unblock an IP address from UFW and database.
     */
    public function unblock(IpBlock $ipBlock, ?int $adminId = null): array
    {
        $ip = $ipBlock->ip_address;

        try {
            $this->executeSudoCommand(['ufw', 'delete', 'deny', 'from', $ip, 'to', 'any']);
        } catch (\Throwable $e) {
            Log::warning("UFW unblock failed for {$ip}: " . $e->getMessage());
        }

        $ipBlock->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_unblocked',
            'description' => "Unblocked IP address/subnet `{$ip}` from kernel firewall.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['ip' => $ip],
            'new_values' => [],
        ]);

        return [
            'success' => true,
            'message' => "IP / Subnet `{$ip}` has been unblocked from the firewall.",
        ];
    }

    /**
     * Update an IP block record (Reason, Expiry).
     */
    public function updateBlock(IpBlock $ipBlock, array $data, ?int $adminId = null): array
    {
        $reason = !empty($data['reason']) ? trim($data['reason']) : $ipBlock->reason;
        $duration = $data['duration'] ?? null;

        $expiresAt = $duration ? $this->calculateExpiry($duration) : $ipBlock->expires_at;

        $ipBlock->update([
            'reason' => $reason,
            'expires_at' => $expiresAt,
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_block_updated',
            'description' => "Updated block settings for `{$ipBlock->ip_address}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['reason' => $ipBlock->getOriginal('reason')],
            'new_values' => ['reason' => $reason, 'expires_at' => $expiresAt?->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "Block details updated for `{$ipBlock->ip_address}`.",
            'block' => $ipBlock,
        ];
    }

    /**
     * Helper to compute Carbon expiry timestamp.
     */
    protected function calculateExpiry(string $duration): ?Carbon
    {
        return match ($duration) {
            '1h' => now()->addHour(),
            '24h' => now()->addDay(),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '90d' => now()->addDays(90),
            default => null, // permanent
        };
    }
}
