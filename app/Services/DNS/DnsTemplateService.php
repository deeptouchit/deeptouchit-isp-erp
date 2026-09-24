<?php

namespace App\Services\DNS;

use App\Models\ActivityLog;
use App\Models\DnsRecord;
use App\Models\DnsTemplate;
use App\Models\DnsTemplateRecord;
use App\Models\DnsZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DnsTemplateService
{
    protected DnsZoneService $zoneService;

    public function __construct(DnsZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * Ensure standard system templates are seeded.
     */
    public function ensureSystemTemplates(): void
    {
        if (DnsTemplate::count() > 0) {
            return;
        }

        // 1. Standard Web Hosting & Business Mail (Default)
        $standard = DnsTemplate::create([
            'name' => 'Standard Web Hosting & Mail',
            'slug' => 'standard-hosting',
            'description' => 'Default cPanel/DeepTouchHost hosting blueprint with web, mail, FTP, nameserver glue, and SPF/DMARC protection.',
            'is_default' => true,
            'is_system' => true,
            'icon' => 'ServerIcon',
            'status' => 'active',
        ]);

        $stdRecords = [
            ['name' => '@', 'type' => 'NS', 'content' => '%primary_ns%.', 'ttl' => 86400],
            ['name' => '@', 'type' => 'NS', 'content' => '%secondary_ns%.', 'ttl' => 86400],
            ['name' => 'ns1', 'type' => 'A', 'content' => '%ip%', 'ttl' => 86400],
            ['name' => 'ns2', 'type' => 'A', 'content' => '%ip%', 'ttl' => 86400],
            ['name' => '@', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'www', 'type' => 'CNAME', 'content' => '%domain%.', 'ttl' => 3600],
            ['name' => 'ftp', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'mail', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'cpanel', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'webmail', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'mail.%domain%.', 'priority' => 10, 'ttl' => 3600],
            ['name' => '@', 'type' => 'TXT', 'content' => '"v=spf1 a mx ip4:%ip% ~all"', 'ttl' => 3600],
            ['name' => '_dmarc', 'type' => 'TXT', 'content' => '"v=DMARC1; p=none; sp=none;"', 'ttl' => 3600],
        ];
        foreach ($stdRecords as $r) {
            $standard->records()->create($r);
        }

        // 2. Google Workspace Mail Cluster
        $google = DnsTemplate::create([
            'name' => 'Google Workspace (G Suite)',
            'slug' => 'google-workspace',
            'description' => 'Optimized for Google Workspace mail routing with official ASPMX redundancy and Google SPF.',
            'is_default' => false,
            'is_system' => true,
            'icon' => 'SparklesIcon',
            'status' => 'active',
        ]);

        $googleRecords = [
            ['name' => '@', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'www', 'type' => 'CNAME', 'content' => '%domain%.', 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'aspmx.l.google.com.', 'priority' => 1, 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'alt1.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'alt2.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'alt3.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => 'alt4.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600],
            ['name' => '@', 'type' => 'TXT', 'content' => '"v=spf1 include:_spf.google.com ~all"', 'ttl' => 3600],
            ['name' => 'mail', 'type' => 'CNAME', 'content' => 'ghs.googlehosted.com.', 'ttl' => 3600],
        ];
        foreach ($googleRecords as $r) {
            $google->records()->create($r);
        }

        // 3. Microsoft 365 / Office 365 Exchange
        $ms365 = DnsTemplate::create([
            'name' => 'Microsoft 365 / Exchange Online',
            'slug' => 'microsoft-365',
            'description' => 'Microsoft 365 Exchange Online protection with Autodiscover CNAME and Outlook SPF.',
            'is_default' => false,
            'is_system' => true,
            'icon' => 'EnvelopeIcon',
            'status' => 'active',
        ]);

        $msRecords = [
            ['name' => '@', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
            ['name' => 'www', 'type' => 'CNAME', 'content' => '%domain%.', 'ttl' => 3600],
            ['name' => '@', 'type' => 'MX', 'content' => '%domain-dash%.mail.protection.outlook.com.', 'priority' => 0, 'ttl' => 3600],
            ['name' => 'autodiscover', 'type' => 'CNAME', 'content' => 'autodiscover.outlook.com.', 'ttl' => 3600],
            ['name' => '@', 'type' => 'TXT', 'content' => '"v=spf1 include:spf.protection.outlook.com ~all"', 'ttl' => 3600],
        ];
        foreach ($msRecords as $r) {
            $ms365->records()->create($r);
        }

        // 4. Let's Encrypt CAA Strict Security
        $caa = DnsTemplate::create([
            'name' => "Let's Encrypt Strict CAA Security",
            'slug' => 'lets-encrypt-caa',
            'description' => "Restricts SSL certificate issuance exclusively to Let's Encrypt with incident reporting.",
            'is_default' => false,
            'is_system' => true,
            'icon' => 'ShieldCheckIcon',
            'status' => 'active',
        ]);

        $caaRecords = [
            ['name' => '@', 'type' => 'CAA', 'content' => '0 issue "letsencrypt.org"', 'ttl' => 3600],
            ['name' => '@', 'type' => 'CAA', 'content' => '0 issuewild "letsencrypt.org"', 'ttl' => 3600],
            ['name' => '@', 'type' => 'CAA', 'content' => '0 iodef "mailto:security@%domain%"', 'ttl' => 3600],
        ];
        foreach ($caaRecords as $r) {
            $caa->records()->create($r);
        }
    }

    /**
     * Create custom DNS Template.
     */
    public function createTemplate(array $data, ?int $adminId = null): DnsTemplate
    {
        $name = trim($data['name']);
        $slug = Str::slug($name) . '-' . rand(100, 999);

        $template = DnsTemplate::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'is_default' => !empty($data['is_default']),
            'is_system' => false,
            'icon' => $data['icon'] ?? 'ServerIcon',
            'status' => 'active',
        ]);

        if ($template->is_default) {
            DnsTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        if (!empty($data['records']) && is_array($data['records'])) {
            foreach ($data['records'] as $rec) {
                if (!empty($rec['name']) && !empty($rec['content'])) {
                    $template->records()->create([
                        'name' => trim($rec['name']),
                        'type' => strtoupper(trim($rec['type'] ?? 'A')),
                        'content' => trim($rec['content']),
                        'ttl' => (int)($rec['ttl'] ?? 3600),
                        'priority' => !empty($rec['priority']) ? (int)$rec['priority'] : null,
                        'port' => !empty($rec['port']) ? (int)$rec['port'] : null,
                        'weight' => !empty($rec['weight']) ? (int)$rec['weight'] : null,
                    ]);
                }
            }
        }

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_template_created',
            'description' => "Created custom DNS Template `{$name}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['name' => $name, 'slug' => $slug],
        ]);

        return $template;
    }

    /**
     * Update custom DNS Template.
     */
    public function updateTemplate(DnsTemplate $template, array $data, ?int $adminId = null): DnsTemplate
    {
        $template->update([
            'name' => trim($data['name'] ?? $template->name),
            'description' => $data['description'] ?? $template->description,
            'icon' => $data['icon'] ?? $template->icon,
            'is_default' => !empty($data['is_default']),
        ]);

        if ($template->is_default) {
            DnsTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        if (isset($data['records']) && is_array($data['records'])) {
            $template->records()->delete();
            foreach ($data['records'] as $rec) {
                if (!empty($rec['name']) && !empty($rec['content'])) {
                    $template->records()->create([
                        'name' => trim($rec['name']),
                        'type' => strtoupper(trim($rec['type'] ?? 'A')),
                        'content' => trim($rec['content']),
                        'ttl' => (int)($rec['ttl'] ?? 3600),
                        'priority' => !empty($rec['priority']) ? (int)$rec['priority'] : null,
                        'port' => !empty($rec['port']) ? (int)$rec['port'] : null,
                        'weight' => !empty($rec['weight']) ? (int)$rec['weight'] : null,
                    ]);
                }
            }
        }

        return $template;
    }

    /**
     * Set template as global default.
     */
    public function setDefault(DnsTemplate $template): void
    {
        DnsTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        $template->update(['is_default' => true]);
    }

    /**
     * Duplicate an existing template.
     */
    public function duplicateTemplate(DnsTemplate $template): DnsTemplate
    {
        $clone = DnsTemplate::create([
            'name' => "Copy of {$template->name}",
            'slug' => Str::slug("Copy of {$template->name}") . '-' . rand(100, 999),
            'description' => $template->description,
            'is_default' => false,
            'is_system' => false,
            'icon' => $template->icon,
            'status' => 'active',
        ]);

        foreach ($template->records as $r) {
            $clone->records()->create([
                'name' => $r->name,
                'type' => $r->type,
                'content' => $r->content,
                'ttl' => $r->ttl,
                'priority' => $r->priority,
                'port' => $r->port,
                'weight' => $r->weight,
            ]);
        }

        return $clone;
    }

    /**
     * Apply Template to a DNS Zone with dynamic variable interpolation.
     */
    public function applyTemplateToZone(DnsTemplate $template, DnsZone $zone, bool $overwrite = false, ?string $serverIp = null): array
    {
        $serverIp = $serverIp ?: \App\Support\ServerHelper::getPublicIp();
        $domain = $zone->domain;
        $domainDash = str_replace('.', '-', $domain);
        $primaryNs = $zone->primary_ns ?: 'ns1.deeptouchit.com';
        $secondaryNs = $zone->secondary_ns ?: 'ns2.deeptouchit.com';

        if ($overwrite) {
            // Remove existing records for this zone
            $zone->records()->delete();
        }

        $appliedCount = 0;
        foreach ($template->records as $tplRec) {
            // Replace dynamic placeholders
            $content = str_replace(
                ['%domain%', '%ip%', '%primary_ns%', '%secondary_ns%', '%domain-dash%'],
                [$domain, $serverIp, $primaryNs, $secondaryNs, $domainDash],
                $tplRec->content
            );

            $name = str_replace('%domain%', $domain, $tplRec->name);

            // Clean trailing dot for FQDNs
            if (in_array($tplRec->type, ['CNAME', 'NS', 'MX']) && !str_ends_with($content, '.') && !filter_var($content, FILTER_VALIDATE_IP)) {
                $content .= '.';
            }

            $zone->records()->create([
                'name' => $name,
                'type' => $tplRec->type,
                'content' => $content,
                'ttl' => $tplRec->ttl,
                'priority' => $tplRec->priority,
                'port' => $tplRec->port,
                'weight' => $tplRec->weight,
                'status' => 'active',
            ]);
            $appliedCount++;
        }

        // Recompile BIND zone
        $this->zoneService->incrementSerial($zone);

        return [
            'success' => true,
            'message' => "Applied {$appliedCount} records from template `{$template->name}` to `{$zone->domain}`.",
        ];
    }

    /**
     * Bulk apply template to multiple zones.
     */
    public function applyTemplateBulk(DnsTemplate $template, array $zoneIds, bool $overwrite = false): array
    {
        $zones = DnsZone::whereIn('id', $zoneIds)->get();
        $count = 0;

        foreach ($zones as $zone) {
            $this->applyTemplateToZone($template, $zone, $overwrite);
            $count++;
        }

        return [
            'success' => true,
            'message' => "Template `{$template->name}` applied to {$count} DNS zones.",
        ];
    }

    /**
     * Delete custom template.
     */
    public function deleteTemplate(DnsTemplate $template): array
    {
        if ($template->is_system) {
            return ['success' => false, 'error' => 'System blueprint templates cannot be deleted.'];
        }

        $name = $template->name;
        $template->delete();

        return ['success' => true, 'message' => "Template `{$name}` deleted successfully."];
    }
}
