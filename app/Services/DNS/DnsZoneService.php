<?php

namespace App\Services\DNS;

use App\Models\ActivityLog;
use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DnsZoneService
{
    use CommandExecutor;

    protected string $zonesDir = '/etc/bind/zones';
    protected string $namedConfLocal = '/etc/bind/named.conf.local';

    /**
     * Create a new DNS zone with default records and compile BIND zone.
     */
    public function createZone(array $data, ?int $adminId = null): array
    {
        $domain = strtolower(trim($data['domain']));
        $serverIp = $data['server_ip'] ?? \App\Support\ServerHelper::getPublicIp();
        $primaryNs = $data['primary_ns'] ?? 'ns1.deeptouchit.com';
        $secondaryNs = $data['secondary_ns'] ?? 'ns2.deeptouchit.com';
        $adminEmail = $data['admin_email'] ?? 'hostmaster.deeptouchit.com';
        $serial = date('Ymd') . '01';

        // Check if zone exists
        if (DnsZone::where('domain', $domain)->exists()) {
            return ['success' => false, 'error' => "DNS Zone for `{$domain}` already exists."];
        }

        $zone = DnsZone::create([
            'subscription_id' => $data['subscription_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'domain' => $domain,
            'primary_ns' => $primaryNs,
            'secondary_ns' => $secondaryNs,
            'admin_email' => $adminEmail,
            'serial' => $serial,
            'refresh' => 86400,
            'retry' => 7200,
            'expire' => 3600000,
            'ttl' => 86400,
            'status' => 'active',
            'dnssec_enabled' => false,
            'zone_file_path' => "{$this->zonesDir}/db.{$domain}",
        ]);

        // Auto-provision standard records
        if (!empty($data['auto_populate'])) {
            $this->populateStandardRecords($zone, $serverIp, $primaryNs, $secondaryNs);
        }

        // Compile and write BIND zone
        $this->compileZoneFile($zone);
        $this->syncNamedConf();
        $this->reloadBind();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_zone_created',
            'description' => "Created DNS Zone for `{$domain}` with standard records.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['domain' => $domain, 'primary_ns' => $primaryNs, 'server_ip' => $serverIp],
        ]);

        return [
            'success' => true,
            'message' => "DNS Zone for `{$domain}` created and compiled into BIND9 successfully.",
            'zone' => $zone,
        ];
    }

    /**
     * Populate standard web & mail DNS records for a domain.
     */
    public function populateStandardRecords(DnsZone $zone, string $serverIp, string $primaryNs, string $secondaryNs): void
    {
        $records = [
            // Nameservers
            ['name' => '@', 'type' => 'NS', 'content' => rtrim($primaryNs, '.') . '.', 'ttl' => 86400],
            ['name' => '@', 'type' => 'NS', 'content' => rtrim($secondaryNs, '.') . '.', 'ttl' => 86400],

            // Glue Records (for vanity nameservers)
            ['name' => 'ns1', 'type' => 'A', 'content' => $serverIp, 'ttl' => 86400],
            ['name' => 'ns2', 'type' => 'A', 'content' => $serverIp, 'ttl' => 86400],

            // Web Address
            ['name' => '@', 'type' => 'A', 'content' => $serverIp, 'ttl' => 3600],
            ['name' => 'www', 'type' => 'CNAME', 'content' => $zone->domain . '.', 'ttl' => 3600],
            ['name' => 'ftp', 'type' => 'A', 'content' => $serverIp, 'ttl' => 3600],
            ['name' => 'mail', 'type' => 'A', 'content' => $serverIp, 'ttl' => 3600],
            ['name' => 'cpanel', 'type' => 'A', 'content' => $serverIp, 'ttl' => 3600],
            ['name' => 'webmail', 'type' => 'A', 'content' => $serverIp, 'ttl' => 3600],

            // Mail Exchanger
            ['name' => '@', 'type' => 'MX', 'content' => "mail.{$zone->domain}.", 'priority' => 10, 'ttl' => 3600],

            // SPF & DMARC
            ['name' => '@', 'type' => 'TXT', 'content' => "\"v=spf1 a mx ip4:{$serverIp} ~all\"", 'ttl' => 3600],
            ['name' => '_dmarc', 'type' => 'TXT', 'content' => "\"v=DMARC1; p=none; sp=none;\"", 'ttl' => 3600],
        ];

        foreach ($records as $rec) {
            $zone->records()->create($rec);
        }
    }

    /**
     * Compile RFC 1035 BIND9 zone file text and write to disk.
     */
    public function compileZoneFile(DnsZone $zone): string
    {
        $domain = $zone->domain;
        $primaryNs = rtrim($zone->primary_ns, '.') . '.';
        $adminEmail = str_replace('@', '.', $zone->admin_email);
        $adminEmail = rtrim($adminEmail, '.') . '.';
        $serial = $zone->serial;
        $ttl = $zone->ttl ?: 86400;

        $lines = [];
        $lines[] = "; Zone file for {$domain}";
        $lines[] = "; Generated by DeepTouchHost BIND9 Engine at " . date('Y-m-d H:i:s');
        $lines[] = "\$TTL {$ttl}";
        $lines[] = "@   IN  SOA {$primaryNs} {$adminEmail} (";
        $lines[] = "        {$serial} ; Serial";
        $lines[] = "        {$zone->refresh} ; Refresh";
        $lines[] = "        {$zone->retry} ; Retry";
        $lines[] = "        {$zone->expire} ; Expire";
        $lines[] = "        {$zone->ttl} ) ; Minimum TTL";
        $lines[] = "";

        // Group records
        $records = $zone->records()->where('status', 'active')->get();

        // 1. NS Records
        $lines[] = "; Nameservers";
        foreach ($records->where('type', 'NS') as $r) {
            $target = str_ends_with($r->content, '.') ? $r->content : "{$r->content}.";
            $lines[] = sprintf("%-16s %-6s IN  NS  %s", $r->name, $r->ttl, $target);
        }
        $lines[] = "";

        // 2. A & AAAA Records
        $lines[] = "; Address Records";
        foreach ($records->whereIn('type', ['A', 'AAAA']) as $r) {
            $lines[] = sprintf("%-16s %-6s IN  %-4s %s", $r->name, $r->ttl, $r->type, $r->content);
        }
        $lines[] = "";

        // 3. CNAME Records
        $lines[] = "; Aliases (CNAME)";
        foreach ($records->where('type', 'CNAME') as $r) {
            $target = str_ends_with($r->content, '.') ? $r->content : "{$r->content}.";
            $lines[] = sprintf("%-16s %-6s IN  CNAME %s", $r->name, $r->ttl, $target);
        }
        $lines[] = "";

        // 4. MX Records
        $lines[] = "; Mail Exchangers";
        foreach ($records->where('type', 'MX') as $r) {
            $target = str_ends_with($r->content, '.') ? $r->content : "{$r->content}.";
            $lines[] = sprintf("%-16s %-6s IN  MX  %-3d %s", $r->name, $r->ttl, $r->priority ?: 10, $target);
        }
        $lines[] = "";

        // 5. TXT Records (SPF, DKIM, DMARC)
        $lines[] = "; Text & Security Records (SPF, DKIM, DMARC)";
        foreach ($records->where('type', 'TXT') as $r) {
            $content = trim($r->content);
            if (!str_starts_with($content, '"')) {
                $content = '"' . addcslashes($content, '"') . '"';
            }
            $lines[] = sprintf("%-16s %-6s IN  TXT %s", $r->name, $r->ttl, $content);
        }
        $lines[] = "";

        // 6. Other Records (SRV, CAA, PTR)
        foreach ($records->whereIn('type', ['SRV', 'CAA', 'PTR']) as $r) {
            $lines[] = sprintf("%-16s %-6s IN  %-4s %s", $r->name, $r->ttl, $r->type, $r->content);
        }

        $zoneContent = implode("\n", $lines) . "\n";
        $zonePath = "{$this->zonesDir}/db.{$domain}";

        // Write zone file with sudo
        try {
            $tmp = tempnam(sys_get_temp_dir(), 'dns_');
            file_put_contents($tmp, $zoneContent);
            $this->executeSudoCommand(['cp', $tmp, $zonePath]);
            $this->executeSudoCommand(['chmod', '644', $zonePath]);
            @unlink($tmp);
        } catch (\Throwable $e) {
            Log::warning("Failed to write BIND zone file for {$domain}: " . $e->getMessage());
        }

        return $zoneContent;
    }

    /**
     * Synchronize /etc/bind/named.conf.local with all active zones.
     */
    public function syncNamedConf(): void
    {
        $zones = DnsZone::where('status', 'active')->get();
        $confLines = [];
        $confLines[] = "// DeepTouchHost BIND9 Local Zone Configuration";
        $confLines[] = "// Auto-generated by DeepTouchHost Engine at " . date('Y-m-d H:i:s');
        $confLines[] = "";

        foreach ($zones as $z) {
            $confLines[] = "zone \"{$z->domain}\" {";
            $confLines[] = "    type master;";
            $confLines[] = "    file \"{$this->zonesDir}/db.{$z->domain}\";";
            $confLines[] = "    allow-transfer { none; };";
            $confLines[] = "};";
            $confLines[] = "";
        }

        $confContent = implode("\n", $confLines);

        try {
            $tmp = tempnam(sys_get_temp_dir(), 'named_');
            file_put_contents($tmp, $confContent);
            $this->executeSudoCommand(['cp', $tmp, $this->namedConfLocal]);
            $this->executeSudoCommand(['chmod', '644', $this->namedConfLocal]);
            @unlink($tmp);
        } catch (\Throwable $e) {
            Log::warning("Failed to write {$this->namedConfLocal}: " . $e->getMessage());
        }
    }

    /**
     * Reload BIND9 daemon.
     */
    public function reloadBind(): array
    {
        try {
            $output = $this->executeSudoCommand(['systemctl', 'reload', 'named']);
            return ['success' => true, 'output' => $output];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Increment zone serial (YYYYMMDDNN) and update zone file.
     */
    public function incrementSerial(DnsZone $zone): void
    {
        $today = date('Ymd');
        $currentSerial = $zone->serial;

        if (str_starts_with($currentSerial, $today)) {
            $seq = (int)substr($currentSerial, 8);
            $newSeq = sprintf('%02d', $seq + 1);
            $newSerial = $today . $newSeq;
        } else {
            $newSerial = $today . '01';
        }

        $zone->update(['serial' => $newSerial]);
        $this->compileZoneFile($zone);
        $this->reloadBind();
    }

    /**
     * Delete zone and remove from BIND.
     */
    public function deleteZone(DnsZone $zone, ?int $adminId = null): array
    {
        $domain = $zone->domain;
        $zonePath = "{$this->zonesDir}/db.{$domain}";

        try {
            if (File::exists($zonePath)) {
                $this->executeSudoCommand(['rm', '-f', $zonePath]);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        $zone->delete();
        $this->syncNamedConf();
        $this->reloadBind();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_zone_deleted',
            'description' => "Deleted DNS Zone for `{$domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['domain' => $domain],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "DNS Zone for `{$domain}` deleted successfully."];
    }

    /**
     * Live DNS Diagnostics probe.
     */
    public function verifyLiveDns(DnsZone $zone): array
    {
        $domain = $zone->domain;
        $results = [];

        // 1. Authoritative Nameservers
        $nsRecords = @dns_get_record($domain, DNS_NS);
        $results['ns'] = [
            'expected' => [$zone->primary_ns, $zone->secondary_ns],
            'found' => array_map(fn($r) => $r['target'] ?? '', $nsRecords ?: []),
            'status' => !empty($nsRecords) ? 'valid' : 'pending',
        ];

        // 2. A Record (@)
        $aRecords = @dns_get_record($domain, DNS_A);
        $results['a'] = [
            'found' => array_map(fn($r) => $r['ip'] ?? '', $aRecords ?: []),
            'status' => !empty($aRecords) ? 'valid' : 'pending',
        ];

        // 3. MX Records
        $mxRecords = @dns_get_record($domain, DNS_MX);
        $results['mx'] = [
            'found' => array_map(fn($r) => "{$r['pri']} {$r['target']}", $mxRecords ?: []),
            'status' => !empty($mxRecords) ? 'valid' : 'pending',
        ];

        // 4. TXT Records
        $txtRecords = @dns_get_record($domain, DNS_TXT);
        $results['txt'] = [
            'found' => array_map(fn($r) => $r['txt'] ?? '', $txtRecords ?: []),
            'status' => !empty($txtRecords) ? 'valid' : 'pending',
        ];

        return $results;
    }
}
