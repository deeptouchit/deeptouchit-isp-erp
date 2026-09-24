<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Models\IpAllowlist;
use App\Traits\CommandExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IpAllowlistService
{
    use CommandExecutor;

    /**
     * Add an IP address or CIDR subnet to the trusted Allowlist.
     */
    public function allowIp(array $data, ?int $adminId = null): array
    {
        $rawIp = trim($data['ip_address']);
        $label = !empty($data['label']) ? trim($data['label']) : 'Trusted Management IP';
        $scope = $data['scope'] ?? 'global'; // global, ssh_only, database, panel_admin
        $duration = $data['duration'] ?? 'permanent';
        $notes = $data['notes'] ?? null;
        $subscriptionId = $data['subscription_id'] ?? null;

        $isSubnet = str_contains($rawIp, '/');

        // Apply Priority 1 Allow rule in UFW
        $cmd = ['ufw', 'insert', '1', 'allow', 'from', $rawIp, 'to', 'any'];
        if ($scope === 'ssh_only') {
            $cmd[] = 'port';
            $cmd[] = '22';
            $cmd[] = 'proto';
            $cmd[] = 'tcp';
        } elseif ($scope === 'database') {
            $cmd[] = 'port';
            $cmd[] = '3306';
            $cmd[] = 'proto';
            $cmd[] = 'tcp';
        }

        try {
            $this->executeSudoCommand($cmd);
        } catch (\Throwable $e) {
            Log::warning("UFW allow failed for {$rawIp}: " . $e->getMessage());
        }

        $expiresAt = $this->calculateExpiry($duration);

        $allow = IpAllowlist::updateOrCreate(
            ['ip_address' => $rawIp],
            [
                'subscription_id' => $subscriptionId,
                'is_subnet' => $isSubnet,
                'label' => $label,
                'scope' => $scope,
                'notes' => $notes,
                'expires_at' => $expiresAt,
                'status' => 'active',
                'created_by' => $adminId ?: auth()->id() ?: 1,
            ]
        );

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_allowlisted',
            'description' => "Whitelisted IP/Subnet `{$rawIp}` ({$label}) with scope `{$scope}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['ip' => $rawIp, 'label' => $label, 'scope' => $scope, 'expires_at' => $expiresAt?->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "IP / Subnet `{$rawIp}` has been added to the trusted allowlist.",
            'allow' => $allow,
        ];
    }

    /**
     * Update an Allowlist record.
     */
    public function updateAllow(IpAllowlist $allowlist, array $data, ?int $adminId = null): array
    {
        $label = !empty($data['label']) ? trim($data['label']) : $allowlist->label;
        $scope = $data['scope'] ?? $allowlist->scope;
        $notes = $data['notes'] ?? $allowlist->notes;
        $duration = $data['duration'] ?? null;

        $expiresAt = $duration ? $this->calculateExpiry($duration) : $allowlist->expires_at;

        $allowlist->update([
            'label' => $label,
            'scope' => $scope,
            'notes' => $notes,
            'expires_at' => $expiresAt,
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_allowlist_updated',
            'description' => "Updated allowlist record for `{$allowlist->ip_address}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['label' => $allowlist->getOriginal('label')],
            'new_values' => ['label' => $label, 'scope' => $scope, 'expires_at' => $expiresAt?->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "Allowlist record for `{$allowlist->ip_address}` updated.",
            'allow' => $allowlist,
        ];
    }

    /**
     * Remove an IP from Allowlist.
     */
    public function removeAllow(IpAllowlist $allowlist, ?int $adminId = null): array
    {
        $ip = $allowlist->ip_address;
        $scope = $allowlist->scope;

        $cmd = ['ufw', 'delete', 'allow', 'from', $ip, 'to', 'any'];
        if ($scope === 'ssh_only') {
            $cmd[] = 'port';
            $cmd[] = '22';
            $cmd[] = 'proto';
            $cmd[] = 'tcp';
        } elseif ($scope === 'database') {
            $cmd[] = 'port';
            $cmd[] = '3306';
            $cmd[] = 'proto';
            $cmd[] = 'tcp';
        }

        try {
            $this->executeSudoCommand($cmd);
        } catch (\Throwable $e) {
            Log::warning("UFW delete allow failed for {$ip}: " . $e->getMessage());
        }

        $allowlist->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ip_allowlist_removed',
            'description' => "Removed IP/Subnet `{$ip}` from trusted allowlist.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['ip' => $ip],
            'new_values' => [],
        ]);

        return [
            'success' => true,
            'message' => "IP / Subnet `{$ip}` removed from trusted allowlist.",
        ];
    }

    /**
     * Helper to compute Carbon expiry timestamp.
     */
    protected function calculateExpiry(string $duration): ?Carbon
    {
        return match ($duration) {
            '24h' => now()->addDay(),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '90d' => now()->addDays(90),
            default => null, // permanent
        };
    }
}
