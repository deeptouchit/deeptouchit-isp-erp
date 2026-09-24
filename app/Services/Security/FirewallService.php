<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Models\FirewallRule;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class FirewallService
{
    use CommandExecutor;

    /**
     * Get UFW Firewall daemon status and default policies.
     */
    public function getFirewallStatus(): array
    {
        $res = $this->executeSudoCommand(['ufw', 'status', 'verbose']);
        $raw = $res['output'] ?? '';

        $isActive = str_contains($raw, 'Status: active');
        $defaultIncoming = 'deny';
        $defaultOutgoing = 'allow';

        if (preg_match('/Default:\s*(\w+)\s*\(incoming\),\s*(\w+)\s*\(outgoing\)/i', $raw, $matches)) {
            $defaultIncoming = strtolower($matches[1]);
            $defaultOutgoing = strtolower($matches[2]);
        }

        return [
            'is_active' => $isActive,
            'status_text' => $isActive ? 'Active & Protecting' : 'Disabled / Inactive',
            'default_incoming' => $defaultIncoming,
            'default_outgoing' => $defaultOutgoing,
            'raw' => $raw,
        ];
    }

    /**
     * Synchronize firewall rules from UFW into database.
     */
    public function syncFromSystem(): void
    {
        $res = $this->executeSudoCommand(['ufw', 'status', 'numbered']);
        $raw = $res['output'] ?? '';

        if (!str_contains($raw, 'Status: active')) {
            return;
        }

        $lines = explode("\n", $raw);
        $seenPorts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // Match pattern: [ 1] 22/tcp ALLOW IN Anywhere
            if (preg_match('/^\[\s*(\d+)\]\s+([0-9:]+)(?:\/(\w+))?\s+(\w+)\s+(IN|OUT)?\s+(.+)$/i', $line, $matches)) {
                $ruleNum = (int)$matches[1];
                $port = $matches[2];
                $protocol = !empty($matches[3]) ? strtolower($matches[3]) : 'any';
                $action = strtolower($matches[4]); // allow, deny, limit
                $direction = !empty($matches[5]) ? strtolower($matches[5]) : 'in';
                $fromIp = trim($matches[6]);

                // Skip v6 duplicate lines for clean view if v4 exists
                if (str_contains($fromIp, '(v6)')) {
                    continue;
                }

                $key = "{$port}-{$protocol}-{$action}-{$fromIp}";
                if (isset($seenPorts[$key])) {
                    continue;
                }
                $seenPorts[$key] = true;

                $label = $this->guessLabelForPort($port, $protocol);

                FirewallRule::updateOrCreate(
                    [
                        'port' => $port,
                        'protocol' => $protocol,
                        'from_ip' => $fromIp,
                    ],
                    [
                        'label' => $label,
                        'action' => $action,
                        'direction' => $direction,
                        'is_system' => $this->isSystemEssentialPort($port),
                        'status' => 'active',
                        'ufw_rule_number' => $ruleNum,
                    ]
                );
            }
        }
    }

    /**
     * Create a new firewall rule in UFW and database.
     */
    public function createRule(array $data, ?int $adminId = null): array
    {
        $port = trim($data['port']);
        $protocol = strtolower(trim($data['protocol'] ?? 'tcp'));
        $action = strtolower(trim($data['action'] ?? 'allow')); // allow, deny, limit, reject
        $fromIp = !empty($data['from_ip']) && $data['from_ip'] !== 'Anywhere' ? trim($data['from_ip']) : 'any';
        $label = !empty($data['label']) ? trim($data['label']) : $this->guessLabelForPort($port, $protocol);

        // Build UFW Command
        // Example: ufw allow 80/tcp or ufw allow from 1.2.3.4 to any port 3306 proto tcp
        $cmd = ['ufw'];
        if ($action === 'limit') {
            $cmd[] = 'limit';
        } elseif ($action === 'deny') {
            $cmd[] = 'deny';
        } elseif ($action === 'reject') {
            $cmd[] = 'reject';
        } else {
            $cmd[] = 'allow';
        }

        if ($fromIp !== 'any') {
            $cmd[] = 'from';
            $cmd[] = $fromIp;
            $cmd[] = 'to';
            $cmd[] = 'any';
            $cmd[] = 'port';
            $cmd[] = $port;
            if ($protocol !== 'any') {
                $cmd[] = 'proto';
                $cmd[] = $protocol;
            }
        } else {
            if ($protocol !== 'any') {
                $cmd[] = "{$port}/{$protocol}";
            } else {
                $cmd[] = $port;
            }
        }

        $res = $this->executeSudoCommand($cmd);

        $rule = FirewallRule::updateOrCreate(
            [
                'port' => $port,
                'protocol' => $protocol,
                'from_ip' => $fromIp === 'any' ? 'Anywhere' : $fromIp,
            ],
            [
                'label' => $label,
                'action' => $action,
                'direction' => 'in',
                'is_system' => $this->isSystemEssentialPort($port),
                'status' => 'active',
            ]
        );

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'firewall_rule_created',
            'description' => "Created firewall rule: {$action} {$port}/{$protocol} from {$fromIp}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['port' => $port, 'protocol' => $protocol, 'action' => $action, 'from' => $fromIp],
        ]);

        return [
            'success' => true,
            'message' => "Firewall rule `{$action} {$port}/{$protocol}` applied successfully.",
            'rule' => $rule,
        ];
    }

    /**
     * Update an existing firewall rule.
     */
    public function updateRule(FirewallRule $rule, array $data, ?int $adminId = null): array
    {
        // 1. Remove old rule from UFW
        $oldPort = $rule->port;
        $oldProto = $rule->protocol;
        $oldAction = $rule->action;
        $oldFrom = $rule->from_ip;

        $delCmd = ['ufw', 'delete'];
        if ($oldAction === 'limit') {
            $delCmd[] = 'limit';
        } elseif ($oldAction === 'deny') {
            $delCmd[] = 'deny';
        } else {
            $delCmd[] = 'allow';
        }

        if ($oldFrom !== 'Anywhere' && $oldFrom !== 'any') {
            $delCmd[] = 'from';
            $delCmd[] = $oldFrom;
            $delCmd[] = 'to';
            $delCmd[] = 'any';
            $delCmd[] = 'port';
            $delCmd[] = $oldPort;
            if ($oldProto !== 'any') {
                $delCmd[] = 'proto';
                $delCmd[] = $oldProto;
            }
        } else {
            if ($oldProto !== 'any') {
                $delCmd[] = "{$oldPort}/{$oldProto}";
            } else {
                $delCmd[] = $oldPort;
            }
        }
        $this->executeSudoCommand($delCmd);

        // 2. Add updated rule in UFW
        $newPort = trim($data['port'] ?? $oldPort);
        $newProto = strtolower(trim($data['protocol'] ?? $oldProto));
        $newAction = strtolower(trim($data['action'] ?? $oldAction));
        $newFrom = !empty($data['from_ip']) && $data['from_ip'] !== 'Anywhere' ? trim($data['from_ip']) : 'any';
        $newLabel = !empty($data['label']) ? trim($data['label']) : $this->guessLabelForPort($newPort, $newProto);

        $addCmd = ['ufw'];
        if ($newAction === 'limit') {
            $addCmd[] = 'limit';
        } elseif ($newAction === 'deny') {
            $addCmd[] = 'deny';
        } elseif ($newAction === 'reject') {
            $addCmd[] = 'reject';
        } else {
            $addCmd[] = 'allow';
        }

        if ($newFrom !== 'any') {
            $addCmd[] = 'from';
            $addCmd[] = $newFrom;
            $addCmd[] = 'to';
            $addCmd[] = 'any';
            $addCmd[] = 'port';
            $addCmd[] = $newPort;
            if ($newProto !== 'any') {
                $addCmd[] = 'proto';
                $addCmd[] = $newProto;
            }
        } else {
            if ($newProto !== 'any') {
                $addCmd[] = "{$newPort}/{$newProto}";
            } else {
                $addCmd[] = $newPort;
            }
        }
        $this->executeSudoCommand($addCmd);

        $rule->update([
            'label' => $newLabel,
            'port' => $newPort,
            'protocol' => $newProto,
            'action' => $newAction,
            'from_ip' => $newFrom === 'any' ? 'Anywhere' : $newFrom,
        ]);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'firewall_rule_updated',
            'description' => "Updated firewall rule `{$newLabel}` ({$newAction} {$newPort}/{$newProto}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['port' => $oldPort, 'protocol' => $oldProto, 'action' => $oldAction],
            'new_values' => ['port' => $newPort, 'protocol' => $newProto, 'action' => $newAction],
        ]);

        return [
            'success' => true,
            'message' => "Firewall rule `{$newLabel}` updated successfully.",
            'rule' => $rule,
        ];
    }

    /**
     * Delete firewall rule.
     */
    public function deleteRule(FirewallRule $rule, ?int $adminId = null): array
    {
        $port = $rule->port;
        $protocol = $rule->protocol;
        $action = $rule->action;
        $fromIp = $rule->from_ip;

        $cmd = ['ufw', 'delete'];
        if ($action === 'limit') {
            $cmd[] = 'limit';
        } elseif ($action === 'deny') {
            $cmd[] = 'deny';
        } else {
            $cmd[] = 'allow';
        }

        if ($fromIp !== 'Anywhere' && $fromIp !== 'any') {
            $cmd[] = 'from';
            $cmd[] = $fromIp;
            $cmd[] = 'to';
            $cmd[] = 'any';
            $cmd[] = 'port';
            $cmd[] = $port;
            if ($protocol !== 'any') {
                $cmd[] = 'proto';
                $cmd[] = $protocol;
            }
        } else {
            if ($protocol !== 'any') {
                $cmd[] = "{$port}/{$protocol}";
            } else {
                $cmd[] = $port;
            }
        }

        $this->executeSudoCommand($cmd);
        $rule->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'firewall_rule_deleted',
            'description' => "Deleted firewall rule: {$port}/{$protocol}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['port' => $port, 'protocol' => $protocol],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "Firewall rule for port {$port}/{$protocol} deleted."];
    }

    /**
     * Toggle master UFW firewall power.
     */
    public function toggleMaster(bool $enable, ?int $adminId = null): array
    {
        if ($enable) {
            $this->executeSudoCommand(['ufw', '--force', 'enable']);
            $msg = 'UFW Firewall activated and protecting server.';
        } else {
            $this->executeSudoCommand(['ufw', 'disable']);
            $msg = 'UFW Firewall disabled.';
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'firewall_master_toggled',
            'description' => $msg,
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['enabled' => $enable],
        ]);

        return ['success' => true, 'message' => $msg];
    }

    /**
     * Get active listening network sockets (Ports).
     */
    public function getListeningPorts(): array
    {
        $res = $this->executeCommand(['ss', '-tulnp']);
        $raw = $res['output'] ?? '';
        $ports = [];

        $lines = explode("\n", $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'tcp') || str_starts_with($line, 'udp')) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 5) {
                    $proto = strtoupper($parts[0]);
                    $localAddr = $parts[4];
                    $process = $parts[6] ?? 'Unknown';

                    // Extract port
                    $port = substr(strrchr($localAddr, ':'), 1);
                    if ($port && is_numeric($port)) {
                        $ports[] = [
                            'protocol' => $proto,
                            'port' => (int)$port,
                            'local_address' => $localAddr,
                            'service' => $this->guessServiceByPort((int)$port),
                            'process' => $process,
                        ];
                    }
                }
            }
        }

        // Return unique by port and protocol
        $unique = [];
        foreach ($ports as $p) {
            $k = "{$p['port']}-{$p['protocol']}";
            if (!isset($unique[$k])) {
                $unique[$k] = $p;
            }
        }

        return array_values($unique);
    }

    /**
     * Apply 1-Click Essential Service Presets.
     */
    public function applyPreset(string $presetKey, ?string $fromIp = null, ?int $adminId = null): array
    {
        $rules = [];
        if ($presetKey === 'web') {
            $rules[] = ['port' => '80', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'HTTP Web Server'];
            $rules[] = ['port' => '443', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'HTTPS Web Server'];
        } elseif ($presetKey === 'dns') {
            $rules[] = ['port' => '53', 'protocol' => 'udp', 'action' => 'allow', 'label' => 'BIND9 DNS Server (UDP)'];
            $rules[] = ['port' => '53', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'BIND9 DNS Zone Transfer (TCP)'];
        } elseif ($presetKey === 'mail') {
            $rules[] = ['port' => '25', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'SMTP Mail Relay'];
            $rules[] = ['port' => '465', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'SMTPS SSL Relay'];
            $rules[] = ['port' => '587', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'SMTP Submission'];
            $rules[] = ['port' => '143', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'IMAP Mail Protocol'];
            $rules[] = ['port' => '993', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'IMAPS SSL Protocol'];
            $rules[] = ['port' => '110', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'POP3 Mail Protocol'];
            $rules[] = ['port' => '995', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'POP3S SSL Protocol'];
        } elseif ($presetKey === 'ssh_limit') {
            $rules[] = ['port' => '22', 'protocol' => 'tcp', 'action' => 'limit', 'label' => 'SSH Anti-Bruteforce Rate Limit'];
        } elseif ($presetKey === 'ftp') {
            $rules[] = ['port' => '21', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'FTP Control Port'];
            $rules[] = ['port' => '30000:31000', 'protocol' => 'tcp', 'action' => 'allow', 'label' => 'FTP Passive Data Range'];
        }

        $applied = 0;
        foreach ($rules as $r) {
            if ($fromIp) {
                $r['from_ip'] = $fromIp;
            }
            $this->createRule($r, $adminId);
            $applied++;
        }

        return ['success' => true, 'message' => "Applied {$applied} firewall rules for preset `{$presetKey}`."];
    }

    protected function guessLabelForPort(string $port, string $proto): string
    {
        return match ($port) {
            '22' => 'SSH Remote Administration',
            '80' => 'HTTP Web Server',
            '443' => 'HTTPS SSL Web Server',
            '53' => 'BIND9 DNS Nameserver',
            '25' => 'SMTP Mail Relay (Postfix)',
            '465' => 'SMTPS Secure Mail Relay',
            '587' => 'SMTP Mail Submission',
            '110' => 'POP3 Mail Service',
            '995' => 'POP3S Secure Mail Service',
            '143' => 'IMAP Mail Service',
            '993' => 'IMAPS Secure Mail Service',
            '21' => 'FTP Server Control',
            '30000:31000' => 'FTP Passive Data Transfer',
            '3306' => 'MySQL / MariaDB Database Server',
            '5432' => 'PostgreSQL Database Server',
            '6379' => 'Redis In-Memory Cache',
            '8891' => 'OpenDKIM Milter Daemon',
            '6001' => 'Laravel Echo WebSocket Server',
            default => "Custom Port {$port}/{$proto}",
        };
    }

    protected function isSystemEssentialPort(string $port): bool
    {
        return in_array($port, ['22', '80', '443', '53', '25', '587']);
    }

    protected function guessServiceByPort(int $port): string
    {
        return match ($port) {
            22 => 'SSH Server',
            80 => 'Nginx HTTP',
            443 => 'Nginx HTTPS',
            53 => 'BIND9 DNS (named)',
            25 => 'Postfix SMTP',
            465 => 'Postfix SMTPS',
            587 => 'Postfix Submission',
            110 => 'Dovecot POP3',
            995 => 'Dovecot POP3S',
            143 => 'Dovecot IMAP',
            993 => 'Dovecot IMAPS',
            3306 => 'MySQL / MariaDB',
            5432 => 'PostgreSQL',
            6379 => 'Redis Server',
            8891 => 'OpenDKIM',
            6001 => 'WebSockets Echo',
            default => 'Listening Service',
        };
    }
}
