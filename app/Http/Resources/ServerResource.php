<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'server_group_id' => $this->server_group_id,
            'server_group_name' => $this->serverGroup?->name,
            'name' => $this->name,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'primary_ip' => $this->primary_ip,
            'ipv6' => $this->ipv6,
            'server_type' => $this->server_type?->value ?? (string) $this->server_type,
            'environment' => $this->environment?->value ?? (string) $this->environment,
            'status' => $this->status?->value ?? (string) $this->status,
            'health_status' => $this->health_status?->value ?? (string) $this->health_status,
            'is_master' => (bool) $this->is_master,
            'os_name' => $this->os_name ?? $this->os,
            'os_version' => $this->os_version,
            'kernel_version' => $this->kernel_version,
            'architecture' => $this->architecture,
            'cpu_cores' => (int) $this->cpu_cores,
            'total_ram' => (int) $this->total_ram,
            'used_ram' => (int) $this->used_ram,
            'total_disk' => (int) $this->total_disk,
            'used_disk' => (int) $this->used_disk,
            'load_avg_1min' => (float) $this->load_avg_1min,
            'load_avg_5min' => (float) $this->load_avg_5min,
            'load_avg_15min' => (float) $this->load_avg_15min,
            'ssh_port' => (int) $this->ssh_port,
            'ssh_user' => $this->ssh_user,
            'auth_type' => $this->auth_type?->value ?? (string) $this->auth_type,
            'ssh_host_key_policy' => $this->ssh_host_key_policy,
            'ssh_host_key_fingerprint' => $this->ssh_host_key_fingerprint,
            'trusted_ssh_host_key_fingerprint' => $this->trusted_ssh_host_key_fingerprint,
            'agent_version' => $this->agent_version,
            'agent_installed_at' => $this->agent_installed_at?->toIso8601String(),
            'last_ping_at' => $this->last_ping_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_health_check_at' => $this->last_health_check_at?->toIso8601String(),
            'maintenance_at' => $this->maintenance_at?->toIso8601String(),
            'maintenance_reason' => $this->maintenance_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
