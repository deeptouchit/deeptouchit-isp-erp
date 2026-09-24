<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Data\Infrastructure\Servers\ServerDiscoveryData;
use App\Data\Infrastructure\Servers\ServerVerificationResult;
use App\Models\Server;

class FakeRemoteServerClient implements RemoteServerClientInterface
{
    protected bool $shouldSucceed = true;
    protected ?string $failureReason = null;
    protected ?string $failureErrorCode = null;
    protected bool $isRetryable = false;

    public function setShouldSucceed(bool $succeed, ?string $reason = null, ?string $errorCode = null, bool $isRetryable = false): self
    {
        $this->shouldSucceed = $succeed;
        $this->failureReason = $reason;
        $this->failureErrorCode = $errorCode;
        $this->isRetryable = $isRetryable;
        return $this;
    }

    public function verifyConnectivity(Server $server): ServerVerificationResult
    {
        if (!$this->shouldSucceed) {
            return ServerVerificationResult::failed(
                message: $this->failureReason ?? "Failed to connect to host {$server->ip_address}: Connection timed out",
                errorCode: $this->failureErrorCode ?? 'SERVER_CONNECTION_TIMEOUT',
                isRetryable: $this->isRetryable,
                durationMs: 50.0
            );
        }

        return ServerVerificationResult::successful(
            message: "Successfully connected to {$server->hostname} ({$server->ip_address}) via SSH port {$server->ssh_port}",
            osFamily: 'Linux',
            detectedArchitecture: 'x86_64',
            durationMs: 42.5,
            context: ['auth_type' => $server->auth_type->value]
        );
    }

    public function discoverHardware(Server $server): ServerDiscoveryData
    {
        return ServerDiscoveryData::fromArray([
            'os_name' => 'Ubuntu',
            'os_version' => '24.04 LTS',
            'kernel_version' => '6.8.0-31-generic',
            'architecture' => 'x86_64',
            'cpu_cores' => 8,
            'total_ram' => 16384,
            'total_disk' => 320,
            'hostname' => $server->hostname ?: 'node1.deeptouchhost.local',
            'detected_services' => ['nginx', 'mysql', 'php8.3-fpm', 'ufw', 'fail2ban'],
            'installed_runtimes' => ['php8.1', 'php8.2', 'php8.3', 'php8.4', 'node20', 'python3'],
        ]);
    }

    public function executeAllowlistedOperation(Server $server, string $operation, array $params = []): array
    {
        return [
            'success' => true,
            'operation' => $operation,
            'output' => "Simulated output for operation: {$operation}",
            'exit_code' => 0,
        ];
    }
}
