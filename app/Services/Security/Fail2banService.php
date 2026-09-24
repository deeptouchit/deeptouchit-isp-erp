<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class Fail2banService
{
    use CommandExecutor;

    /**
     * Get complete Fail2ban daemon overview, status and all jails metrics.
     */
    public function getOverview(): array
    {
        $statusRes = $this->executeSudoCommand(['fail2ban-client', 'status']);
        $raw = $statusRes['output'] ?? '';

        $isActive = $statusRes['success'] && !str_contains($raw, 'Unable to connect to server');

        $jails = [];
        $jailNames = [];

        if ($isActive && preg_match('/Jail list:\s*(.+)$/m', $raw, $matches)) {
            $jailNames = array_map('trim', explode(',', trim($matches[1])));
            $jailNames = array_filter($jailNames);
        }

        $totalCurrentlyBanned = 0;
        $totalHistoricBanned = 0;
        $totalFailedAttempts = 0;
        $allBannedIps = [];

        foreach ($jailNames as $jName) {
            $jRes = $this->executeSudoCommand(['fail2ban-client', 'status', $jName]);
            $jRaw = $jRes['output'] ?? '';

            $curFailed = 0;
            $totFailed = 0;
            $curBanned = 0;
            $totBanned = 0;
            $bannedList = [];

            if (preg_match('/Currently failed:\s*(\d+)/i', $jRaw, $m)) {
                $curFailed = (int)$m[1];
            }
            if (preg_match('/Total failed:\s*(\d+)/i', $jRaw, $m)) {
                $totFailed = (int)$m[1];
            }
            if (preg_match('/Currently banned:\s*(\d+)/i', $jRaw, $m)) {
                $curBanned = (int)$m[1];
            }
            if (preg_match('/Total banned:\s*(\d+)/i', $jRaw, $m)) {
                $totBanned = (int)$m[1];
            }
            if (preg_match('/Banned IP list:\s*(.*)$/m', $jRaw, $m)) {
                $ips = preg_split('/\s+/', trim($m[1]));
                $bannedList = array_values(array_filter($ips));
            }

            $totalCurrentlyBanned += $curBanned;
            $totalHistoricBanned += $totBanned;
            $totalFailedAttempts += $totFailed;

            foreach ($bannedList as $bIp) {
                $allBannedIps[] = [
                    'ip' => $bIp,
                    'jail' => $jName,
                    'service' => $this->guessServiceForJail($jName),
                    'status' => 'banned',
                ];
            }

            $jails[] = [
                'name' => $jName,
                'service' => $this->guessServiceForJail($jName),
                'currently_failed' => $curFailed,
                'total_failed' => $totFailed,
                'currently_banned' => $curBanned,
                'total_banned' => $totBanned,
                'banned_ips' => $bannedList,
                'status' => 'active',
            ];
        }

        return [
            'is_active' => $isActive,
            'status_text' => $isActive ? 'Active & Enforcing Jails' : 'Daemon Inactive / Stopped',
            'jails' => $jails,
            'banned_ips' => $allBannedIps,
            'stats' => [
                'is_active' => $isActive,
                'active_jails' => count($jails),
                'currently_banned' => $totalCurrentlyBanned,
                'total_banned' => $totalHistoricBanned,
                'total_failed' => $totalFailedAttempts,
            ],
        ];
    }

    /**
     * Ban an IP manually in a specific jail.
     */
    public function banIp(string $jail, string $ip, ?int $adminId = null): array
    {
        $jail = trim($jail);
        $ip = trim($ip);

        $res = $this->executeSudoCommand(['fail2ban-client', 'set', $jail, 'banip', $ip]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'fail2ban_ip_banned',
            'description' => "Manually banned IP `{$ip}` in Fail2ban jail `{$jail}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['ip' => $ip, 'jail' => $jail],
        ]);

        return [
            'success' => true,
            'message' => "IP `{$ip}` banned in Fail2ban jail `{$jail}`.",
        ];
    }

    /**
     * Unban an IP from a specific jail.
     */
    public function unbanIp(string $jail, string $ip, ?int $adminId = null): array
    {
        $jail = trim($jail);
        $ip = trim($ip);

        $res = $this->executeSudoCommand(['fail2ban-client', 'set', $jail, 'unbanip', $ip]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'fail2ban_ip_unbanned',
            'description' => "Unbanned IP `{$ip}` from Fail2ban jail `{$jail}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['ip' => $ip, 'jail' => $jail],
            'new_values' => [],
        ]);

        return [
            'success' => true,
            'message' => "IP `{$ip}` unbanned from Fail2ban jail `{$jail}`.",
        ];
    }

    /**
     * Flush / Unban all banned IPs.
     */
    public function unbanAll(?string $jail = null, ?int $adminId = null): array
    {
        if ($jail) {
            $this->executeSudoCommand(['fail2ban-client', 'unban', '--jail', $jail]);
            $msg = "Flushed all banned IPs in jail `{$jail}`.";
        } else {
            $this->executeSudoCommand(['fail2ban-client', 'unban', '--all']);
            $msg = "Flushed all active Fail2ban bans across all jails.";
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'fail2ban_flushed',
            'description' => $msg,
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['jail' => $jail],
        ]);

        return ['success' => true, 'message' => $msg];
    }

    /**
     * Restart Fail2ban system daemon.
     */
    public function restartDaemon(?int $adminId = null): array
    {
        $this->executeSudoCommand(['systemctl', 'restart', 'fail2ban']);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'fail2ban_restarted',
            'description' => "Restarted Fail2ban intrusion prevention daemon.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => 'Fail2ban system service restarted successfully.'];
    }

    /**
     * Elevate temporary Fail2ban ban into a permanent kernel drop.
     */
    public function elevateToPermanentBlock(string $jail, string $ip, ?int $adminId = null): array
    {
        // 1. Unban from fail2ban
        $this->unbanIp($jail, $ip, $adminId);

        // 2. Add to permanent IpBlock
        $blockService = app(IpBlockService::class);
        $res = $blockService->blockIp([
            'ip_address' => $ip,
            'reason' => "Fail2ban `{$jail}` brute-force attack elevated to permanent blacklist",
            'duration' => 'permanent',
            'type' => 'system',
        ], $adminId);

        return [
            'success' => true,
            'message' => "IP `{$ip}` released from Fail2ban and elevated to Permanent Kernel Blocklist.",
        ];
    }

    protected function guessServiceForJail(string $jail): string
    {
        return match (strtolower($jail)) {
            'sshd' => 'SSH Remote Terminal (Port 22)',
            'nginx-http-auth' => 'Nginx HTTP Authentication',
            'nginx-botsearch' => 'Nginx Bot & Exploit Scanner Mitigation',
            'nginx-bad-request' => 'Nginx Malicious Request Filter',
            'postfix', 'postfix-sasl' => 'Postfix SMTP Authentication',
            'dovecot' => 'Dovecot IMAP/POP3 Mail Login',
            default => "Protected Jail ({$jail})",
        };
    }
}
