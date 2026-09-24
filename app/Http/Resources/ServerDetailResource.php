<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ServerDetailResource extends ServerResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        $base['server_group'] = $this->whenLoaded('serverGroup', function () {
            return $this->serverGroup ? [
                'id' => $this->serverGroup->id,
                'uuid' => $this->serverGroup->uuid,
                'name' => $this->serverGroup->name,
                'slug' => $this->serverGroup->slug,
                'location' => $this->serverGroup->location,
            ] : null;
        });

        $base['services'] = $this->whenLoaded('services', function () {
            return $this->services->map(fn ($svc) => [
                'id' => $svc->id,
                'service_name' => $svc->service_name,
                'display_name' => $svc->display_name,
                'service_type' => $svc->service_type,
                'status' => $svc->status?->value ?? (string) $svc->status,
                'enabled' => (bool) $svc->enabled,
                'last_checked_at' => $svc->last_checked_at?->toIso8601String(),
            ]);
        });

        $base['latest_metric'] = $this->whenLoaded('latestMetric', function () {
            return $this->latestMetric ? [
                'id' => $this->latestMetric->id,
                'cpu_usage' => (float) $this->latestMetric->cpu_usage,
                'memory_used' => (int) $this->latestMetric->memory_used,
                'memory_total' => (int) $this->latestMetric->memory_total,
                'disk_used' => (int) $this->latestMetric->disk_used,
                'disk_total' => (int) $this->latestMetric->disk_total,
                'disk_usage' => (float) $this->latestMetric->disk_usage,
                'recorded_at' => $this->latestMetric->recorded_at?->toIso8601String(),
            ] : null;
        });

        $base['recent_events'] = $this->whenLoaded('events', function () {
            return $this->events->map(fn ($evt) => [
                'id' => $evt->id,
                'event_type' => $evt->event_type?->value ?? (string) $evt->event_type,
                'severity' => $evt->severity?->value ?? (string) $evt->severity,
                'message' => $evt->message,
                'metadata' => $evt->metadata,
                'occurred_at' => $evt->occurred_at?->toIso8601String(),
            ]);
        });

        $base['subscriptions_count'] = (int) $this->subscriptions_count ?? $this->subscriptions()->count();

        return $base;
    }
}
