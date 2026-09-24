<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latest = $this->latestMetric;

        return [
            'server' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'hostname' => $this->hostname,
                'ip_address' => $this->ip_address,
                'status' => $this->status?->value ?? (string) $this->status,
                'health_status' => $this->health_status?->value ?? (string) $this->health_status,
                'last_seen_at' => $this->last_seen_at?->toIso8601String(),
                'last_ping_at' => $this->last_ping_at?->toIso8601String(),
            ],
            'current' => [
                'cpu_usage' => $latest ? (float) $latest->cpu_usage : 0.0,
                'load_1m' => (float) $this->load_avg_1min,
                'load_5m' => (float) $this->load_avg_5min,
                'load_15m' => (float) $this->load_avg_15min,
                'memory_used' => (int) $this->used_ram,
                'memory_total' => (int) $this->total_ram,
                'memory_usage_percent' => $this->total_ram > 0 ? round(($this->used_ram / $this->total_ram) * 100, 2) : 0,
                'disk_used' => (int) $this->used_disk,
                'disk_total' => (int) $this->total_disk,
                'disk_usage_percent' => $this->total_disk > 0 ? round(($this->used_disk / $this->total_disk) * 100, 2) : 0,
                'network_rx' => $latest ? (int) $latest->network_rx : 0,
                'network_tx' => $latest ? (int) $latest->network_tx : 0,
                'recorded_at' => $latest?->recorded_at?->toIso8601String(),
            ],
            'alerts_count' => $this->whenLoaded('alertStates', fn () => $this->alertStates->whereIn('state', ['warning', 'critical'])->count(), 0),
        ];
    }
}
