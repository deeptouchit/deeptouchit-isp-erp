<?php

namespace App\Services\Audit;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialAuditService
{
    /**
     * Write a financial event with traceable actor/request context while excluding
     * credentials and free-form sensitive input from immutable audit metadata.
     */
    public function record(Tenant|int $tenant, string $eventType, string $description, array $metadata = [], ?Request $request = null): TenantActivityLog
    {
        $request ??= request();
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        $user = Auth::user();
        $requestReference = (string) ($request->header('X-Request-ID') ?: $request->input('request_key', ''));

        return TenantActivityLog::create([
            'tenant_id' => $tenantId,
            'actor_type' => $user ? $user::class : 'system',
            'actor_id' => $user?->id,
            'actor_name' => $user?->name ?? __('System Automation Engine'),
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $this->sanitize(array_merge($metadata, [
                'request_reference' => $requestReference !== ''
                    ? substr(hash('sha256', $requestReference), 0, 20)
                    : null,
            ])),
        ]);
    }

    private function sanitize(array $metadata): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'authorization', 'notes', 'reason', 'user_agent', 'request_key'];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if (collect($sensitiveKeys)->contains(fn (string $sensitiveKey) => str_contains($normalizedKey, $sensitiveKey))) {
                unset($metadata[$key]);
                continue;
            }

            if (is_array($value)) {
                $metadata[$key] = $this->sanitize($value);
            }
        }

        return array_filter($metadata, static fn ($value) => $value !== null);
    }
}
