<?php

namespace App\Services\DNS;

use App\Models\ActivityLog;
use App\Models\DnsRecord;
use App\Models\DnsZone;
use Illuminate\Support\Facades\Log;

class DnsRecordService
{
    protected DnsZoneService $zoneService;

    public function __construct(DnsZoneService $zoneService)
    {
        $this->zoneService = $zoneService;
    }

    /**
     * Create DNS Record and compile BIND zone.
     */
    public function createRecord(DnsZone $zone, array $data, ?int $adminId = null): array
    {
        $name = trim($data['name'] ?? '@');
        $type = strtoupper(trim($data['type']));
        $content = trim($data['content']);
        $ttl = (int)($data['ttl'] ?? 3600);
        $priority = !empty($data['priority']) ? (int)$data['priority'] : null;
        $port = !empty($data['port']) ? (int)$data['port'] : null;
        $weight = !empty($data['weight']) ? (int)$data['weight'] : null;

        // Clean up CNAME and NS trailing dots
        if (in_array($type, ['CNAME', 'NS', 'MX']) && !str_ends_with($content, '.') && !filter_var($content, FILTER_VALIDATE_IP)) {
            $content .= '.';
        }

        $record = $zone->records()->create([
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'priority' => $priority,
            'port' => $port,
            'weight' => $weight,
            'status' => 'active',
        ]);

        // Increment serial and compile BIND zone
        $this->zoneService->incrementSerial($zone);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_record_created',
            'description' => "Added {$type} record `{$name}` -> `{$content}` for zone `{$zone->domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['zone' => $zone->domain, 'type' => $type, 'name' => $name, 'content' => $content],
        ]);

        return [
            'success' => true,
            'message' => "{$type} record for `{$name}` added to `{$zone->domain}` successfully.",
            'record' => $record,
        ];
    }

    /**
     * Update DNS Record and compile BIND zone.
     */
    public function updateRecord(DnsRecord $record, array $data, ?int $adminId = null): array
    {
        $zone = $record->zone;
        $oldValues = $record->toArray();

        $name = trim($data['name'] ?? $record->name);
        $type = strtoupper(trim($data['type'] ?? $record->type));
        $content = trim($data['content'] ?? $record->content);
        $ttl = (int)($data['ttl'] ?? $record->ttl);
        $priority = isset($data['priority']) ? (int)$data['priority'] : $record->priority;
        $port = isset($data['port']) ? (int)$data['port'] : $record->port;
        $weight = isset($data['weight']) ? (int)$data['weight'] : $record->weight;

        if (in_array($type, ['CNAME', 'NS', 'MX']) && !str_ends_with($content, '.') && !filter_var($content, FILTER_VALIDATE_IP)) {
            $content .= '.';
        }

        $record->update([
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'priority' => $priority,
            'port' => $port,
            'weight' => $weight,
        ]);

        $this->zoneService->incrementSerial($zone);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_record_updated',
            'description' => "Updated {$type} record `{$name}` for zone `{$zone->domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => $oldValues,
            'new_values' => $record->fresh()->toArray(),
        ]);

        return [
            'success' => true,
            'message' => "{$type} record for `{$name}` updated successfully.",
            'record' => $record,
        ];
    }

    /**
     * Delete DNS record and sync BIND.
     */
    public function deleteRecord(DnsRecord $record, ?int $adminId = null): array
    {
        $zone = $record->zone;
        $desc = "Deleted {$record->type} record `{$record->name}` from zone `{$zone->domain}`.";

        $record->delete();
        $this->zoneService->incrementSerial($zone);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'dns_record_deleted',
            'description' => $desc,
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "DNS record removed from `{$zone->domain}`."];
    }

    /**
     * Apply 1-Click Provider Presets (Google Workspace, Microsoft 365, etc.).
     */
    public function applyPreset(DnsZone $zone, string $presetKey): array
    {
        if ($presetKey === 'google_workspace') {
            // Remove existing MX
            $zone->records()->where('type', 'MX')->delete();
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => 'aspmx.l.google.com.', 'priority' => 1, 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => 'alt1.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => 'alt2.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => 'alt3.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => 'alt4.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600]);
            // Google SPF
            $zone->records()->where('type', 'TXT')->where('content', 'like', '%v=spf1%')->delete();
            $zone->records()->create(['name' => '@', 'type' => 'TXT', 'content' => '"v=spf1 include:_spf.google.com ~all"', 'ttl' => 3600]);
        } elseif ($presetKey === 'microsoft_365') {
            $dashDomain = str_replace('.', '-', $zone->domain);
            $zone->records()->where('type', 'MX')->delete();
            $zone->records()->create(['name' => '@', 'type' => 'MX', 'content' => "{$dashDomain}.mail.protection.outlook.com.", 'priority' => 0, 'ttl' => 3600]);
            $zone->records()->create(['name' => 'autodiscover', 'type' => 'CNAME', 'content' => 'autodiscover.outlook.com.', 'ttl' => 3600]);
            $zone->records()->where('type', 'TXT')->where('content', 'like', '%v=spf1%')->delete();
            $zone->records()->create(['name' => '@', 'type' => 'TXT', 'content' => '"v=spf1 include:spf.protection.outlook.com ~all"', 'ttl' => 3600]);
        } elseif ($presetKey === 'lets_encrypt_caa') {
            $zone->records()->where('type', 'CAA')->delete();
            $zone->records()->create(['name' => '@', 'type' => 'CAA', 'content' => '0 issue "letsencrypt.org"', 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'CAA', 'content' => '0 issuewild "letsencrypt.org"', 'ttl' => 3600]);
            $zone->records()->create(['name' => '@', 'type' => 'CAA', 'content' => '0 iodef "mailto:security@' . $zone->domain . '"', 'ttl' => 3600]);
        }

        $this->zoneService->incrementSerial($zone);

        return ['success' => true, 'message' => "Preset `{$presetKey}` applied to `{$zone->domain}` successfully."];
    }
}
