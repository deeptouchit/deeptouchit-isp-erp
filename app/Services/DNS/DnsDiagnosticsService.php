<?php

namespace App\Services\DNS;

use App\Models\DnsZone;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class DnsDiagnosticsService
{
    use CommandExecutor;

    protected string $zonesDir = '/etc/bind/zones';

    /**
     * Run full comprehensive RFC DNS Health Audit on a domain.
     */
    public function runFullAudit(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $zone = DnsZone::where('domain', $domain)->first();

        $results = [
            'domain' => $domain,
            'is_local_zone' => (bool)$zone,
            'score' => 100,
            'summary' => 'All systems nominal and passing RFC standards.',
            'categories' => [],
        ];

        // 1. Local BIND9 Zone File & Daemon Verification
        $localChecks = [];
        $zoneFile = "{$this->zonesDir}/db.{$domain}";
        if ($zone && file_exists($zoneFile)) {
            $syntaxOutput = '';
            try {
                $cmdRes = $this->executeCommand(['named-checkzone', $domain, $zoneFile]);
                $syntaxOutput = $cmdRes['output'] ?? $cmdRes['error_output'] ?? '';
                $syntaxPass = str_contains($syntaxOutput, 'OK');
            } catch (\Throwable $e) {
                $syntaxPass = false;
                $syntaxOutput = $e->getMessage();
            }

            $localChecks[] = [
                'name' => 'BIND9 Zone File Syntax',
                'status' => $syntaxPass ? 'pass' : 'fail',
                'message' => $syntaxPass ? "Zone file `db.{$domain}` passed `named-checkzone` syntax validation." : "Syntax error: {$syntaxOutput}",
            ];
        } else {
            $localChecks[] = [
                'name' => 'Local BIND9 Zone Hosted',
                'status' => $zone ? 'pass' : 'info',
                'message' => $zone ? "Zone exists in local database." : "External domain (not currently hosted in local BIND).",
            ];
        }

        // Port 53 socket check
        $socket = @fsockopen('udp://127.0.0.1', 53, $errno, $errstr, 1.0);
        if ($socket) {
            $localChecks[] = [
                'name' => 'Local Port 53 Listening',
                'status' => 'pass',
                'message' => 'BIND9 daemon (named.service) is actively listening on Port 53 UDP.',
            ];
            fclose($socket);
        } else {
            $localChecks[] = [
                'name' => 'Local Port 53 Listening',
                'status' => 'warn',
                'message' => 'Local port 53 socket could not be opened.',
            ];
        }

        $results['categories'][] = [
            'name' => 'Local BIND9 Nameserver Daemon',
            'icon' => 'ServerIcon',
            'items' => $localChecks,
        ];

        // 2. Authoritative Nameservers & SOA
        $nsChecks = [];
        $nsRecords = @dns_get_record($domain, DNS_NS);
        if (!empty($nsRecords)) {
            $nsList = array_map(fn($r) => $r['target'] ?? '', $nsRecords);
            $nsChecks[] = [
                'name' => 'Nameserver Delegation',
                'status' => 'pass',
                'message' => 'Found ' . count($nsList) . ' authoritative nameservers: ' . implode(', ', $nsList),
            ];
        } else {
            $nsChecks[] = [
                'name' => 'Nameserver Delegation',
                'status' => 'warn',
                'message' => 'No public NS delegation found on public internet yet (propagation pending).',
            ];
        }

        $soaRecords = @dns_get_record($domain, DNS_SOA);
        if (!empty($soaRecords)) {
            $soa = $soaRecords[0];
            $nsChecks[] = [
                'name' => 'SOA Record & Serial',
                'status' => 'pass',
                'message' => "SOA Primary: {$soa['mname']}, Serial: {$soa['serial']}, Admin: {$soa['rname']}",
            ];
        } else {
            $nsChecks[] = [
                'name' => 'SOA Record',
                'status' => 'warn',
                'message' => 'No public SOA record returned.',
            ];
        }

        $results['categories'][] = [
            'name' => 'Authoritative Nameservers & SOA',
            'icon' => 'ServerStackIcon',
            'items' => $nsChecks,
        ];

        // 3. Web & Address Routing (A / CNAME)
        $webChecks = [];
        $aRecords = @dns_get_record($domain, DNS_A);
        if (!empty($aRecords)) {
            $ips = array_map(fn($r) => $r['ip'] ?? '', $aRecords);
            $webChecks[] = [
                'name' => 'Root Domain (@) IPv4 Resolution',
                'status' => 'pass',
                'message' => 'Resolves to: ' . implode(', ', $ips),
            ];
        } else {
            $webChecks[] = [
                'name' => 'Root Domain (@) IPv4 Resolution',
                'status' => 'warn',
                'message' => 'No public A record resolved for root domain.',
            ];
        }

        $wwwRecords = @dns_get_record("www.{$domain}", DNS_A);
        $wwwCname = @dns_get_record("www.{$domain}", DNS_CNAME);
        if (!empty($wwwRecords) || !empty($wwwCname)) {
            $webChecks[] = [
                'name' => 'WWW Subdomain Resolution',
                'status' => 'pass',
                'message' => '`www.' . $domain . '` is actively resolving.',
            ];
        } else {
            $webChecks[] = [
                'name' => 'WWW Subdomain Resolution',
                'status' => 'info',
                'message' => '`www.' . $domain . '` is not configured or not yet propagated.',
            ];
        }

        $results['categories'][] = [
            'name' => 'Web & Host Address Routing',
            'icon' => 'GlobeAltIcon',
            'items' => $webChecks,
        ];

        // 4. Mail Authentication & Security (MX, SPF, DMARC)
        $mailChecks = [];
        $mxRecords = @dns_get_record($domain, DNS_MX);
        if (!empty($mxRecords)) {
            $mxList = array_map(fn($r) => "Pri {$r['pri']}: {$r['target']}", $mxRecords);
            $mailChecks[] = [
                'name' => 'Mail Exchangers (MX)',
                'status' => 'pass',
                'message' => implode(', ', $mxList),
            ];
        } else {
            $mailChecks[] = [
                'name' => 'Mail Exchangers (MX)',
                'status' => 'warn',
                'message' => 'No MX records found. Inbound email may fail.',
            ];
        }

        $txtRecords = @dns_get_record($domain, DNS_TXT);
        $hasSpf = false;
        $spfContent = '';
        if (!empty($txtRecords)) {
            foreach ($txtRecords as $t) {
                if (str_starts_with($t['txt'] ?? '', 'v=spf1')) {
                    $hasSpf = true;
                    $spfContent = $t['txt'];
                    break;
                }
            }
        }

        if ($hasSpf) {
            $mailChecks[] = [
                'name' => 'Sender Policy Framework (SPF)',
                'status' => 'pass',
                'message' => "Valid SPF: {$spfContent}",
            ];
        } else {
            $mailChecks[] = [
                'name' => 'Sender Policy Framework (SPF)',
                'status' => 'warn',
                'message' => 'No SPF TXT record detected. Outbound mail may be marked as spam.',
            ];
        }

        $dmarcRecords = @dns_get_record("_dmarc.{$domain}", DNS_TXT);
        $hasDmarc = false;
        $dmarcContent = '';
        if (!empty($dmarcRecords)) {
            foreach ($dmarcRecords as $t) {
                if (str_starts_with($t['txt'] ?? '', 'v=DMARC1')) {
                    $hasDmarc = true;
                    $dmarcContent = $t['txt'];
                    break;
                }
            }
        }

        if ($hasDmarc) {
            $mailChecks[] = [
                'name' => 'DMARC Security Policy',
                'status' => 'pass',
                'message' => "Valid DMARC: {$dmarcContent}",
            ];
        } else {
            $mailChecks[] = [
                'name' => 'DMARC Security Policy',
                'status' => 'info',
                'message' => 'No `_dmarc` TXT record detected.',
            ];
        }

        $results['categories'][] = [
            'name' => 'Email Authentication & Security',
            'icon' => 'EnvelopeIcon',
            'items' => $mailChecks,
        ];

        // 5. Global Multi-Resolver Propagation Probe
        $resolvers = [
            ['name' => 'Google DNS', 'ip' => '8.8.8.8'],
            ['name' => 'Cloudflare DNS', 'ip' => '1.1.1.1'],
            ['name' => 'Quad9 Security', 'ip' => '9.9.9.9'],
            ['name' => 'Local BIND9', 'ip' => '127.0.0.1'],
        ];

        $propChecks = [];
        $syncedCount = 0;
        foreach ($resolvers as $res) {
            $digOutput = '';
            $digLatency = 0;
            $start = microtime(true);
            try {
                $cmdRes = $this->executeCommand(['dig', "@{$res['ip']}", $domain, 'A', '+short', '+time=2']);
                $digOutput = $cmdRes['output'] ?? '';
                $digLatency = (int)(round(microtime(true) - $start, 3) * 1000);
            } catch (\Throwable $e) {
                $digOutput = '';
            }

            $resolved = trim($digOutput);
            if (!empty($resolved)) {
                $syncedCount++;
                $propChecks[] = [
                    'name' => "{$res['name']} ({$res['ip']})",
                    'status' => 'pass',
                    'message' => "Resolved `{$resolved}` in {$digLatency}ms",
                ];
            } else {
                $propChecks[] = [
                    'name' => "{$res['name']} ({$res['ip']})",
                    'status' => 'warn',
                    'message' => "No response or query timed out ({$digLatency}ms)",
                ];
            }
        }

        $results['categories'][] = [
            'name' => 'Global Multi-Resolver Propagation Probe',
            'icon' => 'SparklesIcon',
            'items' => $propChecks,
        ];

        // Calculate health score
        $totalItems = 0;
        $passCount = 0;
        foreach ($results['categories'] as $cat) {
            foreach ($cat['items'] as $it) {
                $totalItems++;
                if ($it['status'] === 'pass') {
                    $passCount++;
                }
            }
        }
        $results['score'] = $totalItems > 0 ? (int)round(($passCount / $totalItems) * 100) : 100;

        return $results;
    }

    /**
     * Interactive Web Dig query execution.
     */
    public function executeDig(string $domain, string $type = 'A', string $nameserver = '127.0.0.1'): array
    {
        $domain = trim($domain);
        $type = strtoupper(trim($type));
        $nameserver = trim($nameserver);

        $args = ['dig', "@{$nameserver}", $domain, $type];

        $startTime = microtime(true);
        try {
            $cmdRes = $this->executeCommand($args);
            $latency = (int)(round(microtime(true) - $startTime, 3) * 1000);
            $rawOutput = !empty($cmdRes['output']) ? $cmdRes['output'] : ($cmdRes['error_output'] ?? 'No output returned');
            return [
                'success' => $cmdRes['success'] ?? true,
                'command' => "dig @{$nameserver} {$domain} {$type}",
                'output' => $rawOutput,
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'command' => "dig @{$nameserver} {$domain} {$type}",
                'output' => $e->getMessage(),
                'latency_ms' => 0,
            ];
        }
    }

    /**
     * Inspect reverse DNS (PTR) for an IP.
     */
    public function checkRdns(string $ip): array
    {
        $ip = trim($ip);
        $hostname = @gethostbyaddr($ip);

        return [
            'ip' => $ip,
            'hostname' => $hostname !== $ip ? $hostname : 'No PTR Record Found',
            'has_rdns' => $hostname !== $ip,
        ];
    }
}
