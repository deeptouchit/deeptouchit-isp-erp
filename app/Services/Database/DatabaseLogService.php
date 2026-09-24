<?php

namespace App\Services\Database;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DatabaseLogService
{
    use CommandExecutor;

    /**
     * Read and parse Database Engine Logs (MySQL / PostgreSQL).
     */
    public function getEngineLogs(string $engine = 'mysql', int $lines = 100, ?string $levelFilter = null): array
    {
        $logFile = '/var/log/mysql/error.log';

        if ($engine === 'postgres') {
            $logFile = '/var/log/postgresql/postgresql-18-main.log';
            if (!file_exists($logFile)) {
                $candidates = glob('/var/log/postgresql/*.log');
                if (!empty($candidates)) {
                    $logFile = $candidates[0];
                }
            }
        }

        $lines = max(10, min(1000, $lines));
        $res = $this->executeSudoCommand(['tail', '-n', (string)$lines, $logFile]);
        $rawOutput = $res['output'] ?? '';

        // If MySQL error.log is empty or recent, check if there are recent gz logs or journal logs
        if (empty(trim($rawOutput)) && $engine === 'mysql') {
            $journalRes = $this->executeSudoCommand(['journalctl', '-u', 'mysql', '-n', (string)$lines, '--no-pager']);
            if (!empty(trim($journalRes['output'] ?? ''))) {
                $rawOutput = $journalRes['output'];
                $logFile = 'journalctl (mysql.service)';
            }
        }

        $entries = [];
        $rawLines = explode("\n", trim($rawOutput));

        foreach ($rawLines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            $parsed = $this->parseLogLine($trimmed, $engine);

            if ($levelFilter && $levelFilter !== 'all') {
                if (strtolower($parsed['level']) !== strtolower($levelFilter)) {
                    continue;
                }
            }

            $entries[] = $parsed;
        }

        // Reverse to show newest first
        $entries = array_reverse($entries);

        $sizeBytes = (file_exists($logFile) && is_file($logFile)) ? @filesize($logFile) : strlen($rawOutput);
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
     * Parse log line into structured timestamp, level, message, pid.
     */
    protected function parseLogLine(string $line, string $engine): array
    {
        $timestamp = null;
        $level = 'NOTE';
        $message = $line;
        $pid = null;

        if ($engine === 'mysql') {
            // MySQL format: 2026-08-30T00:24:00.123456Z 0 [System] [MY-010116] [Server] ...
            // Or: 2026-08-30T00:24:00.123456Z 0 [ERROR] ...
            if (preg_match('/^([0-9\-T:\.Z]+)\s+([0-9]+)\s+\[([^\]]+)\]\s+(.*)$/', $line, $m)) {
                $timestamp = $m[1];
                $pid = (int)$m[2];
                $level = strtoupper(trim($m[3]));
                $message = trim($m[4]);
            } elseif (preg_match('/^([A-Za-z]{3}\s+[0-9]+\s+[0-9:]+)\s+\S+\s+mysqld\[([0-9]+)\]:\s+(.*)$/', $line, $m)) {
                // Journalctl format
                $timestamp = $m[1];
                $pid = (int)$m[2];
                $message = trim($m[3]);
                if (stripos($message, 'error') !== false) $level = 'ERROR';
                elseif (stripos($message, 'warn') !== false) $level = 'WARNING';
                else $level = 'NOTE';
            }
        } else {
            // PostgreSQL format: 2026-08-30 19:24:00.123 UTC [12345] LOG:  database system was shut down...
            if (preg_match('/^([0-9\-]+\s+[0-9:.]+\s+\S+)\s+\[([0-9]+)\]\s+([A-Z]+):\s+(.*)$/', $line, $m)) {
                $timestamp = $m[1];
                $pid = (int)$m[2];
                $level = strtoupper(trim($m[3]));
                $message = trim($m[4]);
            }
        }

        return [
            'timestamp' => $timestamp ?: date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'pid' => $pid,
            'raw' => $line,
        ];
    }

    /**
     * Clear / Truncate database engine log file.
     */
    public function clearEngineLog(string $engine = 'mysql', ?int $adminId = null): array
    {
        $logFile = $engine === 'postgres' ? '/var/log/postgresql/postgresql-18-main.log' : '/var/log/mysql/error.log';

        if (!file_exists($logFile)) {
            return ['success' => false, 'error' => "Log file does not exist: {$logFile}"];
        }

        $res = $this->executeSudoCommand(['truncate', '-s', '0', $logFile]);

        if ($res['success']) {
            $this->logAction($adminId, "database_{$engine}_log_cleared", ['log_file' => $logFile]);

            return [
                'success' => true,
                'message' => strtoupper($engine) . " server log truncated successfully.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to truncate log file: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    /**
     * Get Database Operations Audit Trail.
     */
    public function getAuditLogs(int $limit = 60, ?string $search = null): array
    {
        $query = ActivityLog::with('user')
            ->where(function ($q) {
                $q->where('action', 'like', '%database%')
                  ->orWhere('action', 'like', '%mysql%')
                  ->orWhere('action', 'like', '%postgres%')
                  ->orWhere('action', 'like', '%db%');
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
     * Telemetry stats for Logs view.
     */
    public function getLogStats(string $engine): array
    {
        $engineLogs = $this->getEngineLogs($engine, 300);
        $entries = $engineLogs['entries'] ?? [];

        $errorCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['ERROR', 'ALERT', 'CRITICAL', 'FATAL', 'PANIC'])));
        $warningCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['WARNING', 'WARN'])));
        $noteCount = count(array_filter($entries, fn($e) => in_array($e['level'], ['NOTE', 'LOG', 'INFO', 'SYSTEM'])));
        $auditCount = ActivityLog::where('action', 'like', '%database%')
            ->orWhere('action', 'like', '%mysql%')
            ->orWhere('action', 'like', '%postgres%')
            ->count();

        return [
            'total_entries' => count($entries),
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'note_count' => $noteCount,
            'audit_count' => $auditCount,
            'log_size' => $engineLogs['size'],
            'log_file' => $engineLogs['file'],
        ];
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Database Log Manager: {$action}",
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
