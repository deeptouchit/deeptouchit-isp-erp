<?php

namespace App\Services\Infrastructure\Servers;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServerService
{
    protected ServerLifecycleService $lifecycleService;
    protected ServerCredentialService $credentialService;
    protected ServerEventService $eventService;

    public function __construct(
        ServerLifecycleService $lifecycleService,
        ServerCredentialService $credentialService,
        ServerEventService $eventService
    ) {
        $this->lifecycleService = $lifecycleService;
        $this->credentialService = $credentialService;
        $this->eventService = $eventService;
    }

    /**
     * Register a new hosting server node into the cluster.
     */
    public function registerServer(array $attributes): Server
    {
        return DB::transaction(function () use ($attributes) {
            $ip = $attributes['ip_address'] ?? $attributes['primary_ip'] ?? '127.0.0.1';

            $server = Server::create([
                'uuid' => (string) Str::uuid(),
                'server_group_id' => $attributes['server_group_id'] ?? null,
                'name' => $attributes['name'],
                'hostname' => $attributes['hostname'],
                'ip_address' => $ip,
                'primary_ip' => $ip,
                'ipv6' => $attributes['ipv6'] ?? null,
                'server_type' => $attributes['server_type'] ?? ServerType::WORKER,
                'environment' => $attributes['environment'] ?? ServerEnvironment::PRODUCTION,
                'status' => ServerStatus::PENDING,
                'health_status' => ServerHealthStatus::UNKNOWN,
                'is_master' => (bool) ($attributes['is_master'] ?? false),
                'ssh_port' => (int) ($attributes['ssh_port'] ?? 22),
                'ssh_user' => $attributes['ssh_user'] ?? 'root',
                'auth_type' => $attributes['auth_type'] ?? ServerAuthType::PASSWORD,
                'encrypted_ssh_password' => $attributes['ssh_password'] ?? null,
                'encrypted_ssh_key' => $attributes['ssh_key'] ?? null,
            ]);

            // If a secret was provided, also save it in the dedicated credentials table
            if (!empty($attributes['ssh_password'])) {
                $this->credentialService->storeCredential(
                    $server,
                    ServerCredentialType::SSH_PASSWORD,
                    'Primary SSH Password',
                    $server->ssh_user,
                    $attributes['ssh_password']
                );
            } elseif (!empty($attributes['ssh_key'])) {
                $this->credentialService->storeCredential(
                    $server,
                    ServerCredentialType::SSH_PRIVATE_KEY,
                    'Primary SSH Key',
                    $server->ssh_user,
                    $attributes['ssh_key']
                );
            }

            return $server;
        });
    }

    /**
     * Update server metadata.
     */
    public function updateServer(Server $server, array $attributes): Server
    {
        return DB::transaction(function () use ($server, $attributes) {
            $fillableAttributes = [
                'name', 'hostname', 'ip_address', 'primary_ip', 'ipv6',
                'server_group_id', 'server_type', 'environment',
                'ssh_port', 'ssh_user', 'auth_type'
            ];

            $updateData = array_intersect_key($attributes, array_flip($fillableAttributes));
            $server->update($updateData);

            if (!empty($attributes['ssh_password'])) {
                $server->update(['encrypted_ssh_password' => $attributes['ssh_password']]);
                $this->credentialService->storeCredential(
                    $server,
                    ServerCredentialType::SSH_PASSWORD,
                    'Rotated SSH Password',
                    $server->ssh_user,
                    $attributes['ssh_password']
                );
            }

            if (!empty($attributes['ssh_key'])) {
                $server->update(['encrypted_ssh_key' => $attributes['ssh_key']]);
                $this->credentialService->storeCredential(
                    $server,
                    ServerCredentialType::SSH_PRIVATE_KEY,
                    'Rotated SSH Key',
                    $server->ssh_user,
                    $attributes['ssh_key']
                );
            }

            return $server;
        });
    }

    /**
     * Toggle Maintenance Mode.
     */
    public function setMaintenanceMode(Server $server, bool $enabled, ?string $reason = null): Server
    {
        $targetStatus = $enabled ? ServerStatus::MAINTENANCE : ServerStatus::ACTIVE;
        return $this->lifecycleService->transition($server, $targetStatus, $reason);
    }

    /**
     * Soft delete / Decommission a server node.
     */
    public function deleteServer(Server $server): bool
    {
        if ($server->is_master) {
            throw new \RuntimeException('Cannot delete the cluster Master control plane node.');
        }

        return DB::transaction(function () use ($server) {
            $this->lifecycleService->transition($server, ServerStatus::DECOMMISSIONED, 'Server deleted by administrator');
            return $server->delete();
        });
    }
}
