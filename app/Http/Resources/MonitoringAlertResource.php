<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'server_name' => $this->server?->name,
            'alert_type' => $this->alert_type?->value ?? (string) $this->alert_type,
            'resource_identity' => $this->resource_identity,
            'state' => $this->state?->value ?? (string) $this->state,
            'severity' => $this->severity,
            'current_value' => $this->current_value,
            'threshold_value' => $this->threshold_value,
            'fingerprint' => $this->fingerprint,
            'started_at' => $this->started_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'last_escalated_at' => $this->last_escalated_at?->toIso8601String(),
            'suppressed_until' => $this->suppressed_until?->toIso8601String(),
            'suppression_reason' => $this->suppression_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
