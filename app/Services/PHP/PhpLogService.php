<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpLogService
{
    use CommandExecutor;

    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Read and parse PHP-FPM service error log.
     */
    public function getFpmLogs(string $version, int $lines = 100, ?string $levelFilter = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['entries' => [], 'file' => '', 'size' => '0 KB', 'raw' => ''];
        }

        $logFile = "/var/log/php{$version}-fpm.log";
        if (!file_exists($logFile)) {
            // Also check alternate log path
            $altLog = "/var/log/php-fpm/php{$version}-fpm.log";
            if (file_exists($altLog)) {
                $logFile = $altLog;
            }
        }

        $lines = max(10, min(1000, $lines));
        $res = $this->executeSudoCommand(['tail', '-n', (string)$lines, $logFile]);
        $rawOutput = $res['output'] ?? '';

        $entries = [];
        $rawLines = explode("\n", trim($rawOutput));

        foreach ($rawLines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            $parsed = $this->parseLogLine($trimmed);
            
            if ($levelFilter && $levelFilter !== 'all') {
                if (strtolower($parsed['level']) !== strtolower($levelFilter)) {
                    continue;
                }
            }

            $entries[] = $parsed;
        }

        // Reverse to show newest first
        $entries = array_reverse($entries);

        $sizeBytes = file_exists($logFile) ? @filesize($logFile) : 0;
        $sizeFormatted = round($sizeBytes / 1024, 1) . ' KB';
        if ($sizeBytes > 1024 * 1024) {
            $sizeFormatted = round($sizeBytes / 1024 / 1024, 2) . ' MB';
        }

        return [
            'file' => $logFile,
            'size' => $sizeFormatted,
            'size_bytes' => $sizeBytes,
            'entries' => $entries,
            'raw' => $rawOutput,
        ];
    }

    /**
     * Parse an FPM log line into structured components.
     */
    protected function parseLogLine(string $line): array
    {
        // Example format: [30-Aug-2026 19:10:00] NOTICE: [pool www] child 12345 started
        // Or: [30-Aug-2026 19:10:00] WARNING: [pool www] child 12345 exited
        // Or: [30-Aug-2026 19:10:00] ERROR: failed to post process...
        $timestamp = null;
        $level = 'NOTICE';
        $message = $line;
        $pid = null;

        if (preg_match('/^\[([^\]]+)\]\s+([A-Z]+):\s+(.*)$/', $line, $m)) {
            $timestamp = $m[1];
            $level = strtoupper(trim($m[2]));
            $message = trim($m[3]);
        } elseif (preg_match('/^\[([^\]]+)\]\s+(.*)$/', $line, $m)) {
            $timestamp = $m[1];
            $message = trim($m[2]);
        }

        if (preg_match('/child\s+([0-9]+)/i', $message, $pm)) {
            $pid = (int)$pm[1];
        }

        return [
            'timestamp' => $timestamp ?: date('d-M-Y H:i:s'),
            'level' => $level,
            'message' => $message,
            'pid' => $pid,
            'raw' => $line,
        ];
    }

    /**
     * Clear / Truncate FPM log file.
     */
    public function clearFpmLogs(string $version, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $logFile = "/var/log/php{$version}-fpm.log";
        if (!file_exists($logFile)) {
            $this->executeSudoCommand(['touch', $logFile]);
        }

        $res = $this->executeSudoCommand(['truncate', '-s', '0', $logFile]);

        if ($res['success'] || !file_exists($logFile)) {
            $this->logAction($adminId, "php_{$version}_logs_cleared", ['log_file' => $logFile]);

            return [
                'success' => true,
                'message' => "PHP {$version}-FPM service log truncated successfully.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to truncate log file: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    /**
     * Get PHP Audit Trail Activity Logs with user relation.
     */
    public function getAuditLogs(int $limit = 50, ?string $search = null): array
    {
        $query = ActivityLog::with('user')
            ->where(function ($q) {
                $q->where('action', 'like', 'php_%')
                  ->orWhere('action', 'like', '%php%')
                  ->orWhere('action', 'like', '%opcache%')
                  ->orWhere('action', 'like', '%fpm%')
                  ->orWhere('action', 'like', 'website_php_%');
            })
            ->latest();

        if ($search && trim($search)) {
            $s = trim($search);
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        return $query->take($limit)->get()->toArray();
    }

    /**
     * Get Telemetry stats for Logs view.
     */
    public function getLogStats(string $version): array
    {
        $fpmLogs = $this->getFpmLogs($version, 300);
        $entries = $fpmLogs['entries'] ?? [];

        $errorCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['ERROR', 'ALERT', 'CRITICAL'])));
        $warningCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['WARNING', 'WARN'])));
        $noticeCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['NOTICE', 'INFO', 'DEBUG'])));
        $auditCount = ActivityLog::where('action', 'like', '%php%')->count();

        return [
            'total_entries' => count($entries),
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'notice_count' => $noticeCount,
            'audit_count' => $auditCount,
            'log_size' => $fpmLogs['size'],
            'log_file' => $fpmLogs['file'],
        ];
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PHP Log Manager: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }
    }
}
