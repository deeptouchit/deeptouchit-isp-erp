<?php

namespace App\Services\Files;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;

class AdminStorageService
{
    use CommandExecutor;

    /**
     * Get complete storage partitions, category breakdowns and inode statistics.
     */
    public function getStorageOverview(): array
    {
        // 1. Primary Root Partition Metrics
        $dfRes = $this->executeSudoCommand(['df', '-B1', '/']);
        $dfOutput = $dfRes['output'] ?? '';

        $totalBytes = 0;
        $usedBytes = 0;
        $freeBytes = 0;
        $percentUsed = 0;

        $lines = explode("\n", trim($dfOutput));
        if (count($lines) >= 2) {
            $cols = preg_split('/\s+/', $lines[1]);
            if (count($cols) >= 5) {
                $totalBytes = (int)$cols[1];
                $usedBytes = (int)$cols[2];
                $freeBytes = (int)$cols[3];
                $percentUsed = (int)rtrim($cols[4], '%');
            }
        }

        // 2. Inode Metrics
        $dfiRes = $this->executeSudoCommand(['df', '-i', '/']);
        $dfiOutput = $dfiRes['output'] ?? '';
        $totalInodes = 0;
        $usedInodes = 0;
        $freeInodes = 0;
        $inodePercent = 0;

        $linesI = explode("\n", trim($dfiOutput));
        if (count($linesI) >= 2) {
            $colsI = preg_split('/\s+/', $linesI[1]);
            if (count($colsI) >= 5) {
                $totalInodes = (int)$colsI[1];
                $usedInodes = (int)$colsI[2];
                $freeInodes = (int)$colsI[3];
                $inodePercent = (int)rtrim($colsI[4], '%');
            }
        }

        // 3. Category Directories Breakdown
        $vhostsBytes = $this->getFolderSize('/var/www/vhosts');
        $deeptouchhostBytes = $this->getFolderSize('/var/www/deeptouchhost');
        $logsBytes = $this->getFolderSize('/var/log');
        $tmpBytes = $this->getFolderSize('/tmp');
        $dbBytes = $this->getFolderSize('/var/lib/mysql');

        $categories = [
            [
                'name' => 'Websites & Virtual Hosts',
                'path' => '/var/www/vhosts',
                'size' => $vhostsBytes,
                'size_formatted' => $this->formatFileSize($vhostsBytes),
                'description' => 'Client websites code, public_html files, and uploads',
                'color' => '#673DE6',
                'icon' => 'globe',
            ],
            [
                'name' => 'DeepTouch Host Panel Core',
                'path' => '/var/www/deeptouchhost',
                'size' => $deeptouchhostBytes,
                'size_formatted' => $this->formatFileSize($deeptouchhostBytes),
                'description' => 'Control panel source code, assets, and cache',
                'color' => '#0284C7',
                'icon' => 'server',
            ],
            [
                'name' => 'System & Web Server Logs',
                'path' => '/var/log',
                'size' => $logsBytes,
                'size_formatted' => $this->formatFileSize($logsBytes),
                'description' => 'Nginx, PHP-FPM, systemd journal, and auth audit logs',
                'color' => '#E11D48',
                'icon' => 'document-text',
            ],
            [
                'name' => 'Databases (MySQL / MariaDB)',
                'path' => '/var/lib/mysql',
                'size' => $dbBytes,
                'size_formatted' => $this->formatFileSize($dbBytes),
                'description' => 'Database tables, InnoDB storage, and binary logs',
                'color' => '#059669',
                'icon' => 'database',
            ],
            [
                'name' => 'Temporary & Cache Storage',
                'path' => '/tmp',
                'size' => $tmpBytes,
                'size_formatted' => $this->formatFileSize($tmpBytes),
                'description' => 'Temporary runtime uploads, session locks, and cache',
                'color' => '#D97706',
                'icon' => 'trash',
            ],
        ];

        // 4. Per-Subscription Storage Breakdown
        $subscriptions = $this->getSubscriptionStorageUsage();

        return [
            'primary_disk' => [
                'total' => $totalBytes,
                'total_formatted' => $this->formatFileSize($totalBytes),
                'used' => $usedBytes,
                'used_formatted' => $this->formatFileSize($usedBytes),
                'free' => $freeBytes,
                'free_formatted' => $this->formatFileSize($freeBytes),
                'percent_used' => $percentUsed,
            ],
            'inodes' => [
                'total' => $totalInodes,
                'total_formatted' => number_format($totalInodes),
                'used' => $usedInodes,
                'used_formatted' => number_format($usedInodes),
                'free' => $freeInodes,
                'free_formatted' => number_format($freeInodes),
                'percent_used' => $inodePercent,
            ],
            'categories' => $categories,
            'subscriptions' => $subscriptions,
            'stats' => [
                'vhosts_size_formatted' => $this->formatFileSize($vhostsBytes),
                'logs_size_formatted' => $this->formatFileSize($logsBytes),
            ],
        ];
    }

    /**
     * Compute disk usage per subscription domain.
     */
    public function getSubscriptionStorageUsage(): array
    {
        $subscriptions = Subscription::with('user')->get();
        $results = [];

        foreach ($subscriptions as $sub) {
            $user = $sub->user ? $sub->user->name : 'N/A';
            $domain = $sub->domain;
            $username = $sub->username;

            // Search possible paths: /var/www/vhosts/{username}/{domain} or /var/www/vhosts/{domain}
            $vhostPath = "/var/www/vhosts/{$username}/{$domain}";
            if (!File::exists($vhostPath)) {
                $vhostPath = "/var/www/vhosts/{$domain}";
            }
            if (!File::exists($vhostPath)) {
                $vhostPath = "/var/www/vhosts/{$username}";
            }

            $diskUsed = File::exists($vhostPath) ? $this->getFolderSize($vhostPath) : 0;
            $inodeCount = File::exists($vhostPath) ? $this->getFolderInodes($vhostPath) : 0;

            // Quota limit in bytes (e.g. from plan or default 10GB = 10737418240)
            $quotaBytes = 10 * 1024 * 1024 * 1024; // 10 GB
            $quotaPercent = $quotaBytes > 0 ? min(100, round(($diskUsed / $quotaBytes) * 100, 1)) : 0;

            $results[] = [
                'id' => $sub->id,
                'domain' => $domain,
                'username' => $username,
                'client_name' => $user,
                'path' => $vhostPath,
                'disk_used' => $diskUsed,
                'disk_used_formatted' => $this->formatFileSize($diskUsed),
                'inodes' => $inodeCount,
                'quota_formatted' => '10 GB',
                'quota_percent' => $quotaPercent,
                'status' => $sub->status,
            ];
        }

        return $results;
    }

    /**
     * Clean system logs using journalctl vacuum.
     */
    public function vacuumSystemLogs(?int $adminId = null): array
    {
        $res = $this->executeSudoCommand(['journalctl', '--vacuum-time=7d']);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'logs_vacuumed',
            'description' => "Vacuumed system logs older than 7 days.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['output' => $res['output'] ?? ''],
        ]);

        return ['success' => true, 'message' => "System journal logs trimmed to 7 days."];
    }

    /**
     * Flush cache and temporary files.
     */
    public function flushTempCache(?int $adminId = null): array
    {
        try {
            \Artisan::call('optimize:clear');
        } catch (\Throwable $e) {
            // ignore
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'cache_flushed',
            'description' => "Flushed application view/config caches and temp runtime buffers.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Application cache and temporary buffers cleared."];
    }

    /**
     * Fast folder size computation using recursive iterator.
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
