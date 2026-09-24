<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class SecurityEventsService
{
    use CommandExecutor;

    /**
     * Get paginated audit events and live OS security logs.
     */
    public function getAuditEvents(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = ActivityLog::with('user')->latest();

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['action']) && $filters['action'] !== 'all') {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get real-time OS Linux SSH / Auth Security Event Stream.
     */
    public function getSystemAuthEvents(int $limit = 30): array
    {
        $res = $this->executeSudoCommand(['journalctl', '-u', 'ssh', '-n', (string)$limit, '--no-pager']);
        $raw = $res['output'] ?? '';
        $events = [];

        $lines = explode("\n", $raw);
        foreach (array_reverse($lines) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $isFailed = str_contains($line, 'Failed password') || str_contains($line, 'authentication failure');
            $isAccepted = str_contains($line, 'Accepted password') || str_contains($line, 'Accepted publickey');
            $isClose = str_contains($line, 'Connection closed') || str_contains($line, 'Connection reset');

            $ip = null;
            if (preg_match('/from\s+([0-9a-f.:]+)/i', $line, $m)) {
                $ip = $m[1];
            } elseif (preg_match('/rhost=([0-9a-f.:]+)/i', $line, $m)) {
                $ip = $m[1];
            } elseif (preg_match('/by\s+([0-9a-f.:]+)/i', $line, $m)) {
                $ip = $m[1];
            }

            $user = 'root';
            if (preg_match('/for\s+(\w+)/i', $line, $m)) {
                $user = $m[1];
            } elseif (preg_match('/user=(\w+)/i', $line, $m)) {
                $user = $m[1];
            }

            $events[] = [
                'raw' => $line,
                'ip' => $ip,
                'user' => $user,
                'type' => $isFailed ? 'failed_auth' : ($isAccepted ? 'accepted_auth' : 'connection_event'),
                'severity' => $isFailed ? 'danger' : ($isAccepted ? 'success' : 'info'),
                'title' => $isFailed ? "Failed SSH Login for `{$user}`" : ($isAccepted ? "Accepted SSH Login for `{$user}`" : "SSH Connection Dropped"),
            ];
        }

        return array_slice($events, 0, $limit);
    }

    /**
     * Get Aggregated Security Metrics.
     */
    public function getMetrics(): array
    {
        $totalLogs = ActivityLog::count();
        $todayLogs = ActivityLog::whereDate('created_at', Carbon::today())->count();
        $uniqueIps = ActivityLog::distinct('ip_address')->count('ip_address');
        $securityActions = ActivityLog::where('action', 'like', '%block%')
            ->orWhere('action', 'like', '%firewall%')
            ->orWhere('action', 'like', '%ssl%')
            ->orWhere('action', 'like', '%fail2ban%')
            ->count();

        return [
            'total_events' => $totalLogs,
            'today_events' => $todayLogs,
            'unique_ips' => $uniqueIps,
            'security_actions' => $securityActions,
        ];
    }

    /**
     * Purge logs older than given days.
     */
    public function purgeLogs(int $days = 30, ?int $adminId = null): array
    {
        $cutoff = Carbon::now()->subDays($days);
        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'audit_logs_purged',
            'description' => "Purged {$deleted} audit log entries older than {$days} days.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['days' => $days, 'deleted' => $deleted],
        ]);

        return ['success' => true, 'message' => "Purged {$deleted} audit log records older than {$days} days."];
    }
}
