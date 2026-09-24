<?php

namespace App\Services\Audit;

use App\Models\Tenant;
use App\Models\TenantActivityLog;

class TenantAuditService
{
    /**
     * Log a tenant activity event.
     */
    public function log(
        Tenant|int|null $tenant,
        string $eventType,
        string $description,
        array $metadata = [],
        ?string $actorType = null,
        ?string $actorName = null,
        ?int $actorId = null
    ): TenantActivityLog {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        $user = auth()->check() ? auth()->user() : null;

        $resolvedActorType = $actorType ?: ($user ? ($user->role === 'owner' ? 'owner' : 'tenant_admin') : 'system_cron');
        $resolvedActorName = $actorName ?: ($user ? $user->name : 'System Automation Engine');
        $resolvedActorId = $actorId ?: ($user ? $user->id : null);

        $ip = request()->ip() ?: '127.0.0.1';
        $ua = request()->userAgent() ?: 'CLI/Automation';

        return TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => $resolvedActorType,
            'actor_id' => $resolvedActorId,
            'actor_name' => $resolvedActorName,
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'metadata' => $metadata,
        ]);
    }
}
