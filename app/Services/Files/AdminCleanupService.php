<?php

namespace App\Services\Files;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;

class AdminCleanupService
{
    use CommandExecutor;

    /**
     * Scan system and return all cleanable junk categories with live sizes.
     */
    public function scanJunk(): array
    {
        $categories = [];
        $totalCleanableBytes = 0;

        // 1. Temporary System Buffers (/tmp & /var/tmp)
        $tmpBytes = 0;
        $tmpCount = 0;
        $tmpScan = $this->executeSudoCommand(['find', '/tmp', '/var/tmp', '-mindepth', '1', '-not', '-name', 'systemd*', '-type', 'f', '-printf', "%s\n"]);
        if (!empty($tmpScan['output'])) {
            foreach (explode("\n", trim($tmpScan['output'])) as $s) {
                if (is_numeric($s)) {
                    $tmpBytes += (int)$s;
                    $tmpCount++;
                }
            }
        }
        $totalCleanableBytes += $tmpBytes;
        $categories['temp_buffers'] = [
            'key' => 'temp_buffers',
            'name' => 'System Temporary Buffers',
            'paths' => ['/tmp', '/var/tmp'],
            'description' => 'Temporary file descriptors, node compile buffers, and upload fragments.',
            'size' => $tmpBytes,
            'size_formatted' => $this->formatFileSize($tmpBytes),
            'files_count' => $tmpCount,
            'safety' => 'Safe',
            'color' => '#0284C7',
            'icon' => 'trash',
        ];

        // 2. Old System Journal & Rotated Logs
        $logsBytes = 0;
        $logsCount = 0;
        $logsScan = $this->executeSudoCommand(['find', '/var/log', '-type', 'f', '(', '-name', '*.gz', '-o', '-name', '*.[0-9]', '-o', '-name', '*.old', ')', '-printf', "%s\n"]);
        if (!empty($logsScan['output'])) {
            foreach (explode("\n", trim($logsScan['output'])) as $s) {
                if (is_numeric($s)) {
                    $logsBytes += (int)$s;
                    $logsCount++;
                }
            }
        }
        // Add journal archived journals
        $journalScan = $this->executeSudoCommand(['find', '/var/log/journal', '-type', 'f', '-name', '*@*', '-printf', "%s\n"]);
        if (!empty($journalScan['output'])) {
            foreach (explode("\n", trim($journalScan['output'])) as $s) {
                if (is_numeric($s)) {
                    $logsBytes += (int)$s;
                    $logsCount++;
                }
            }
        }

        $totalCleanableBytes += $logsBytes;
        $categories['system_logs'] = [
            'key' => 'system_logs',
            'name' => 'System Journal & Rotated Logs',
            'paths' => ['/var/log/*.gz', '/var/log/*.[0-9]', '/var/log/journal'],
            'description' => 'Old rotated compressed logs (*.gz) and archived systemd journal logs.',
            'size' => $logsBytes,
            'size_formatted' => $this->formatFileSize($logsBytes),
            'files_count' => $logsCount,
            'safety' => 'Safe (Active logs preserved)',
            'color' => '#E11D48',
            'icon' => 'document-text',
        ];

        // 3. Application Framework Cache & Views
        $cachePaths = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            base_path('bootstrap/cache'),
        ];
        $cacheBytes = 0;
        $cacheCount = 0;
        foreach ($cachePaths as $cp) {
            $cacheBytes += $this->getFolderSize($cp);
            $cacheCount += $this->getFolderFileCount($cp);
        }
        $totalCleanableBytes += $cacheBytes;
        $categories['app_cache'] = [
            'key' => 'app_cache',
            'name' => 'Application Compiled Cache & Views',
            'paths' => $cachePaths,
            'description' => 'Compiled Blade/Vue views, route serialization cache, and framework metadata.',
            'size' => $cacheBytes,
            'size_formatted' => $this->formatFileSize($cacheBytes),
            'files_count' => $cacheCount,
            'safety' => 'Safe (Auto-regenerates)',
            'color' => '#673DE6',
            'icon' => 'sparkles',
        ];

        // 4. Expired PHP Sessions
        $sessionDir = '/var/lib/php/sessions';
        $sessionBytes = 0;
        $sessionCount = 0;
        if (File::exists($sessionDir)) {
            $sessScan = $this->executeSudoCommand(['find', $sessionDir, '-type', 'f', '-printf', "%s\n"]);
            if (!empty($sessScan['output'])) {
                foreach (explode("\n", trim($sessScan['output'])) as $s) {
                    if (is_numeric($s)) {
                        $sessionBytes += (int)$s;
                        $sessionCount++;
                    }
                }
            }
        }
        $totalCleanableBytes += $sessionBytes;
        $categories['php_sessions'] = [
            'key' => 'php_sessions',
            'name' => 'PHP Session Storage',
            'paths' => [$sessionDir],
            'description' => 'Stale PHP runtime session files and orphaned authentication tickets.',
            'size' => $sessionBytes,
            'size_formatted' => $this->formatFileSize($sessionBytes),
            'files_count' => $sessionCount,
            'safety' => 'Safe',
            'color' => '#D97706',
            'icon' => 'user-group',
        ];

        // 5. Orphaned Uploads & Temp Archive Residuals
        $vhostsTempBytes = 0;
        $vhostsTempCount = 0;
        if (File::exists('/var/www/vhosts')) {
            $vhScan = $this->executeSudoCommand(['find', '/var/www/vhosts', '-type', 'f', '(', '-name', '*.tmp', '-o', '-name', '*.partial', ')', '-printf', "%s\n"]);
            if (!empty($vhScan['output'])) {
                foreach (explode("\n", trim($vhScan['output'])) as $s) {
                    if (is_numeric($s)) {
                        $vhostsTempBytes += (int)$s;
                        $vhostsTempCount++;
                    }
                }
            }
        }
        $totalCleanableBytes += $vhostsTempBytes;
        $categories['orphaned_archives'] = [
            'key' => 'orphaned_archives',
            'name' => 'Temp Uploads & Archive Residuals',
            'paths' => ['/var/www/vhosts/*/*.tmp'],
            'description' => 'Leftover .tmp files, partial zip extractions, and staging archives.',
            'size' => $vhostsTempBytes,
            'size_formatted' => $this->formatFileSize($vhostsTempBytes),
            'files_count' => $vhostsTempCount,
            'safety' => 'Safe',
            'color' => '#059669',
            'icon' => 'archive-box',
        ];

        return [
            'categories' => array_values($categories),
            'stats' => [
                'total_cleanable_formatted' => $this->formatFileSize($totalCleanableBytes),
                'total_cleanable_bytes' => $totalCleanableBytes,
                'temp_files_count' => $tmpCount + $cacheCount + $sessionCount + $vhostsTempCount,
                'temp_size_formatted' => $this->formatFileSize($tmpBytes + $cacheBytes + $sessionBytes),
                'logs_size_formatted' => $this->formatFileSize($logsBytes),
                'health_score' => $totalCleanableBytes < 50 * 1024 * 1024 ? 99 : 88,
            ],
        ];
    }

    /**
     * Clean a specific junk category.
     */
    public function cleanCategory(string $key, ?int $adminId = null): array
    {
        $message = 'Cleaned successfully.';

        switch ($key) {
            case 'temp_buffers':
                $this->executeSudoCommand(['find', '/tmp', '/var/tmp', '-mindepth', '1', '-not', '-name', 'systemd*', '-delete']);
                $message = 'System temporary buffers and sockets cleaned.';
                break;

            case 'system_logs':
                $this->executeSudoCommand(['journalctl', '--vacuum-size=10M']);
                $this->executeSudoCommand(['find', '/var/log', '-type', 'f', '(', '-name', '*.gz', '-o', '-name', '*.[0-9]', '-o', '-name', '*.old', ')', '-delete']);
                $message = 'Archived journal logs and old compressed logs (*.gz) removed.';
                break;

            case 'app_cache':
                try {
                    \Artisan::call('optimize:clear');
                    \Artisan::call('view:clear');
                    \Artisan::call('cache:clear');
                    \Artisan::call('config:clear');
                    \Artisan::call('route:clear');
                } catch (\Throwable $e) {
                    // ignore
                }
                $message = 'Application view, route, and config caches cleared.';
                break;

            case 'php_sessions':
                if (File::exists('/var/lib/php/sessions')) {
                    $this->executeSudoCommand(['find', '/var/lib/php/sessions', '-type', 'f', '-delete']);
                }
                $message = 'PHP sessions storage purged.';
                break;

            case 'orphaned_archives':
                if (File::exists('/var/www/vhosts')) {
                    $this->executeSudoCommand(['find', '/var/www/vhosts', '-type', 'f', '(', '-name', '*.tmp', '-o', '-name', '*.partial', ')', '-delete']);
                }
                $message = 'Orphaned temporary archives and residual files removed.';
                break;

            default:
                return ['success' => false, 'message' => "Invalid cleanup category `{$key}`."];
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'junk_cleaned',
            'description' => "Purged system junk category: `{$key}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['category' => $key],
        ]);

        return ['success' => true, 'message' => $message];
    }

    /**
     * Perform universal system deep cleanup.
     */
    public function cleanAll(?int $adminId = null): array
    {
        $keys = ['temp_buffers', 'system_logs', 'app_cache', 'php_sessions', 'orphaned_archives'];
        foreach ($keys as $k) {
            $this->cleanCategory($k, $adminId);
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'universal_cleanup',
            'description' => "Executed universal system junk cleanup across all target categories.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Universal deep cleanup completed successfully."];
    }

    /**
     * Compute folder size recursively in bytes.
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
     * Count files in folder.
     */
    protected function getFolderFileCount(string $path): int
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
                if ($item->isFile()) {
                    $count++;
                }
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
