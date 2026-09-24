<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timestamp' => ($this->recorded_at ?? $this->period_start)?->toIso8601String(),
            'cpu' => (float) ($this->cpu_usage ?? $this->cpu_avg ?? 0.0),
            'ram' => (float) ($this->memory_used ?? $this->ram_used_avg ?? $this->ram_avg ?? 0.0),
            'disk' => (float) ($this->disk_usage ?? $this->disk_used_avg ?? $this->disk_avg ?? 0.0),
            'load_1m' => (float) ($this->load_1m ?? $this->load_1m_avg ?? 0.0),
            'network_rx' => (int) ($this->network_rx ?? $this->network_rx_total ?? 0),
            'network_tx' => (int) ($this->network_tx ?? $this->network_tx_total ?? 0),
        ];
    }
}
