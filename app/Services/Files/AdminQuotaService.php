<?php

namespace App\Services\Files;

use App\Models\ActivityLog;
use App\Models\Subscription;
use Illuminate\Support\Facades\File;

class AdminQuotaService
{
    /**
     * Get complete quota overview for subscriptions with cluster statistics.
     */
    public function getQuotasOverview(array $filters = []): array
    {
        $query = Subscription::with(['user', 'plan', 'server']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('domain', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $subscriptions = $query->latest('created_at')->get();

        $quotaList = [];
        $totalAllocatedBytes = 0;
        $totalUsedBytes = 0;
        $criticalAccounts = 0;
        $warningAccounts = 0;

        foreach ($subscriptions as $sub) {
            $vhostPath = "/var/www/vhosts/{$sub->username}/{$sub->domain}";
            if (!File::exists($vhostPath)) {
                $vhostPath = "/var/www/vhosts/{$sub->domain}";
            }
            if (!File::exists($vhostPath)) {
                $vhostPath = "/var/www/vhosts/{$sub->username}";
            }

            $diskUsed = File::exists($vhostPath) ? $this->getFolderSize($vhostPath) : 0;
            $inodeCount = File::exists($vhostPath) ? $this->getFolderInodes($vhostPath) : 0;

            // Determine quota limit in MB (custom override or from plan)
            $planDiskMB = $sub->plan ? (int)$sub->plan->disk_space : 10000;
            $effectiveQuotaMB = $sub->custom_disk_space !== null ? (int)$sub->custom_disk_space : $planDiskMB;
            $isUnlimited = $effectiveQuotaMB === 0;

            $effectiveQuotaBytes = $isUnlimited ? 0 : ($effectiveQuotaMB * 1024 * 1024);
            $quotaPercent = (!$isUnlimited && $effectiveQuotaBytes > 0)
                ? min(100, round(($diskUsed / $effectiveQuotaBytes) * 100, 1))
                : 0;

            // Inode Limit
            $effectiveInodeLimit = $sub->custom_inodes !== null ? (int)$sub->custom_inodes : 250000;
            $inodePercent = $effectiveInodeLimit > 0 ? min(100, round(($inodeCount / $effectiveInodeLimit) * 100, 1)) : 0;

            // Quota health state
            $health = 'normal';
            if (!$isUnlimited && $quotaPercent >= 90) {
                $health = 'critical';
                $criticalAccounts++;
            } elseif (!$isUnlimited && $quotaPercent >= 75) {
                $health = 'warning';
                $warningAccounts++;
            }

            $totalAllocatedBytes += $effectiveQuotaBytes;
            $totalUsedBytes += $diskUsed;

            $quotaList[] = [
                'id' => $sub->id,
                'domain' => $sub->domain,
                'username' => $sub->username,
                'client_name' => $sub->user ? ($sub->user->first_name . ' ' . $sub->user->last_name) : 'N/A',
                'client_email' => $sub->user ? $sub->user->email : 'N/A',
                'plan_name' => $sub->plan ? $sub->plan->name : 'Custom Plan',
                'plan_disk_mb' => $planDiskMB,
                'custom_disk_space' => $sub->custom_disk_space,
                'custom_inodes' => $sub->custom_inodes,
                'effective_quota_mb' => $effectiveQuotaMB,
                'effective_quota_formatted' => $isUnlimited ? 'Unlimited' : $this->formatFileSize($effectiveQuotaBytes),
                'disk_used_bytes' => $diskUsed,
                'disk_used_formatted' => $this->formatFileSize($diskUsed),
                'quota_percent' => $quotaPercent,
                'inodes_used' => $inodeCount,
                'inodes_limit' => $effectiveInodeLimit,
                'inodes_limit_formatted' => number_format($effectiveInodeLimit),
                'inodes_percent' => $inodePercent,
                'health' => $health,
                'status' => $sub->status,
                'path' => $vhostPath,
            ];
        }

        $avgUsagePercent = count($quotaList) > 0 ? round(($totalUsedBytes / max(1, $totalAllocatedBytes)) * 100, 1) : 0;

        return [
            'quotas' => $quotaList,
            'stats' => [
                'total_accounts' => count($quotaList),
                'total_allocated_formatted' => $this->formatFileSize($totalAllocatedBytes),
                'total_used_formatted' => $this->formatFileSize($totalUsedBytes),
                'critical_accounts' => $criticalAccounts,
                'warning_accounts' => $warningAccounts,
                'average_usage_percent' => $avgUsagePercent,
            ],
        ];
    }

    /**
     * Update disk and inode quota overrides for a subscription.
     */
    public function updateQuota(Subscription $subscription, array $data, ?int $adminId = null): array
    {
        $oldValues = [
            'custom_disk_space' => $subscription->custom_disk_space,
            'custom_inodes' => $subscription->custom_inodes,
        ];

        $customDisk = isset($data['custom_disk_space']) && $data['custom_disk_space'] !== '' ? (int)$data['custom_disk_space'] : null;
        $customInodes = isset($data['custom_inodes']) && $data['custom_inodes'] !== '' ? (int)$data['custom_inodes'] : null;

        $subscription->update([
            'custom_disk_space' => $customDisk,
            'custom_inodes' => $customInodes,
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'quota_updated',
            'description' => "Updated storage quota overrides for `{$subscription->domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => [
                'custom_disk_space' => $customDisk,
                'custom_inodes' => $customInodes,
            ],
        ]);

        return ['success' => true, 'message' => "Quota limits updated for '{$subscription->domain}' successfully."];
    }

    /**
     * Fast folder size computation.
     */
    protected function getFolderSize(string $path): int
    {
        if (!File::exists($path) || !is_dir($path)) {
            return 0;
        }

        $size = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $size += $item->getSize();
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $size;
    }

    /**
     * Inode count for folder.
     */
    protected function getFolderInodes(string $path): int
    {
        if (!File::exists($path) || !is_dir($path)) {
            return 0;
        }

        $count = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $count++;
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $count;
    }

    /**
     * Format bytes into human readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . ($units[$i] ?? 'B');
    }
}
