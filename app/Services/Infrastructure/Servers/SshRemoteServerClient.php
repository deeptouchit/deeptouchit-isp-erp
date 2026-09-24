<?php

namespace App\Services\Infrastructure\Servers;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Data\Infrastructure\Servers\ServerDiscoveryData;
use App\Data\Infrastructure\Servers\ServerVerificationResult;
use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\Servers\RemoteOperation;
use App\Exceptions\Infrastructure\Servers\ServerException;
use App\Exceptions\Infrastructure\Servers\ServerVerificationException;
use App\Models\Server;
use App\Services\Infrastructure\Servers\Parsers\CpuInfoParser;
use App\Services\Infrastructure\Servers\Parsers\DiskInfoParser;
use App\Services\Infrastructure\Servers\Parsers\LoadAvgParser;
use App\Services\Infrastructure\Servers\Parsers\MemInfoParser;
use App\Services\Infrastructure\Servers\Parsers\NetworkInterfacesParser;
use App\Services\Infrastructure\Servers\Parsers\OsReleaseParser;
use App\Services\Infrastructure\Servers\Parsers\SystemdServicesParser;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;
use Throwable;

class SshRemoteServerClient implements RemoteServerClientInterface
{
    protected ServerCredentialService $credentialService;
    protected ServerEventService $eventService;

    // Parsers
    protected OsReleaseParser $osParser;
    protected CpuInfoParser $cpuParser;
    protected MemInfoParser $memParser;
    protected DiskInfoParser $diskParser;
    protected SystemdServicesParser $systemdParser;
    protected NetworkInterfacesParser $networkParser;
    protected LoadAvgParser $loadParser;

    // Safety limits
    protected int $connectionTimeout = 5; // 5s connection timeout
    protected int $operationTimeout = 30; // 30s per-command timeout
    protected int $maxOutputBytes = 524288; // 512KB output cap

    public function __construct(
        ServerCredentialService $credentialService,
        ServerEventService $eventService
    ) {
        $this->credentialService = $credentialService;
        $this->eventService = $eventService;

        $this->osParser = new OsReleaseParser();
        $this->cpuParser = new CpuInfoParser();
        $this->memParser = new MemInfoParser();
        $this->diskParser = new DiskInfoParser();
        $this->systemdParser = new SystemdServicesParser();
        $this->networkParser = new NetworkInterfacesParser();
        $this->loadParser = new LoadAvgParser();
    }

    /**
     * Establish an authenticated SSH2 session with host key verification.
     */
    protected function createSshConnection(Server $server): SSH2
    {
        $host = $server->primary_ip ?: $server->ip_address;
        $port = $server->ssh_port ?: 22;
        $username = $server->ssh_user ?: 'root';

        if (!filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new ServerVerificationException("Invalid host/IP address format: {$host}", 'SSH_INVALID_HOST', false);
        }

        if ($port < 1 || $port > 65535) {
            throw new ServerVerificationException("Invalid SSH port: {$port}", 'SSH_INVALID_PORT', false);
        }

        $ssh = new SSH2($host, $port, $this->connectionTimeout);

        // 1. Resolve host key fingerprint and enforce verification policy
        $serverHostKey = $ssh->getServerPublicHostKey();
        if ($serverHostKey) {
            $fingerprint = 'SHA256:' . base64_encode(hash('sha256', $serverHostKey, true));
            $this->verifyHostKeyPolicy($server, $fingerprint);
        }

        // 2. Resolve credentials in-memory
        $authenticated = false;
        if ($server->auth_type === ServerAuthType::SSH_KEY) {
            $cred = $this->credentialService->getActiveCredential($server, ServerCredentialType::SSH_PRIVATE_KEY);
            $rawKey = $cred ? $cred->encrypted_secret : $server->encrypted_ssh_key;

            if (empty($rawKey)) {
                throw new ServerVerificationException("No SSH private key configured for Server #{$server->id}", 'SSH_CREDENTIAL_MISSING', false);
            }

            try {
                $key = PublicKeyLoader::load($rawKey);
                $authenticated = $ssh->login($username, $key);
            } catch (Throwable $e) {
                throw new ServerVerificationException("Failed to load SSH private key: " . $e->getMessage(), 'SSH_KEY_PARSE_FAILED', false);
            }
        } else {
            // Password Authentication
            $cred = $this->credentialService->getActiveCredential($server, ServerCredentialType::SSH_PASSWORD);
            $rawPassword = $cred ? $cred->encrypted_secret : $server->encrypted_ssh_password;

            if (empty($rawPassword)) {
                throw new ServerVerificationException("No SSH password configured for Server #{$server->id}", 'SSH_CREDENTIAL_MISSING', false);
            }

            $authenticated = $ssh->login($username, $rawPassword);
        }

        if (!$authenticated) {
            $this->eventService->recordEvent(
                $server,
                ServerEventType::SERVER_OFFLINE,
                "SSH authentication failed for user '{$username}' on Server #{$server->id}",
                ServerEventSeverity::CRITICAL
            );

            throw new ServerVerificationException(
                "SSH authentication failed for user '{$username}' on {$host}:{$port}",
                'SSH_AUTHENTICATION_FAILED',
                false
            );
        }

        $ssh->setTimeout($this->operationTimeout);
        return $ssh;
    }

    /**
     * Enforce strict or TOFU host key verification.
     */
    protected function verifyHostKeyPolicy(Server $server, string $remoteFingerprint): void
    {
        $trusted = $server->trusted_ssh_host_key_fingerprint;

        if ($trusted) {
            if ($trusted !== $remoteFingerprint) {
                $this->eventService->recordEvent(
                    $server,
                    ServerEventType::SERVER_OFFLINE,
                    "SECURITY ALERT: SSH host key fingerprint mismatch on Server #{$server->id}. Expected: '{$trusted}', Received: '{$remoteFingerprint}'",
                    ServerEventSeverity::CRITICAL,
                    ['expected' => $trusted, 'received' => $remoteFingerprint]
                );

                throw new ServerVerificationException(
                    "SSH host key verification failed: Remote host fingerprint changed. Potential MITM attack or unrecorded key rotation.",
                    'SSH_HOST_KEY_MISMATCH',
                    false
                );
            }
        } else {
            // Record discovered fingerprint
            if ($server->ssh_host_key_fingerprint !== $remoteFingerprint) {
                $server->update([
                    'ssh_host_key_fingerprint' => $remoteFingerprint,
                    'trusted_ssh_host_key_fingerprint' => ($server->ssh_host_key_policy === 'tofu') ? $remoteFingerprint : null,
                ]);
            }
        }
    }

    /**
     * Verify remote node connectivity.
     */
    public function verifyConnectivity(Server $server): ServerVerificationResult
    {
        $startTime = microtime(true);
        $host = $server->primary_ip ?: $server->ip_address;

        try {
            $ssh = $this->createSshConnection($server);
            $cmd = RemoteOperation::VERIFY_CONNECTIVITY->getPredefinedCommand();
            $output = trim($ssh->exec($cmd));
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (str_contains($output, '__DEEPTOUCHHOST_PONG__')) {
                return ServerVerificationResult::successful(
                    message: "Successfully connected and authenticated with {$host}:{$server->ssh_port} via SSH",
                    osFamily: 'Linux',
                    detectedArchitecture: $server->architecture ?: 'x86_64',
                    durationMs: $durationMs
                );
            }

            return ServerVerificationResult::failed(
                message: "Connected to SSH but handshake ping failed (Unexpected output: {$output})",
                errorCode: 'SSH_PING_MISMATCH',
                isRetryable: true,
                durationMs: $durationMs
            );
        } catch (ServerVerificationException $e) {
            return ServerVerificationResult::failed(
                message: $e->getMessage(),
                errorCode: $e->getErrorCode(),
                isRetryable: $e->isRetryable(),
                durationMs: round((microtime(true) - $startTime) * 1000, 2)
            );
        } catch (Throwable $e) {
            Log::error("[SSH] Connection failure for Server #{$server->id}: " . $e->getMessage());
            return ServerVerificationResult::failed(
                message: "SSH connectivity error: " . $e->getMessage(),
                errorCode: 'SSH_CONNECTION_FAILED',
                isRetryable: true,
                durationMs: round((microtime(true) - $startTime) * 1000, 2)
            );
        }
    }

    /**
     * Run allowlisted discovery probes over SSH session.
     */
    public function discoverHardware(Server $server): ServerDiscoveryData
    {
        $ssh = $this->createSshConnection($server);

        // 1. OS Discovery Probe
        $osRaw = (string) $ssh->exec(RemoteOperation::GET_OS_INFO->getPredefinedCommand());
        $osInfo = $this->osParser->parse($osRaw);

        // 2. Kernel & Arch
        $kernelRaw = trim((string) $ssh->exec(RemoteOperation::GET_KERNEL_INFO->getPredefinedCommand()));
        $archRaw = trim((string) $ssh->exec(RemoteOperation::GET_ARCH_INFO->getPredefinedCommand()));

        // 3. CPU Probe
        $cpuRaw = (string) $ssh->exec(RemoteOperation::GET_CPU_INFO->getPredefinedCommand());
        $cpuInfo = $this->cpuParser->parse($cpuRaw);

        // 4. Memory Probe
        $memRaw = (string) $ssh->exec(RemoteOperation::GET_MEMORY_INFO->getPredefinedCommand());
        $memInfo = $this->memParser->parse($memRaw);

        // 5. Disk Probe
        $diskRaw = (string) $ssh->exec(RemoteOperation::GET_DISK_INFO->getPredefinedCommand());
        $diskInfo = $this->diskParser->parse($diskRaw);

        // 6. Hostname Probe
        $hostnameRaw = trim((string) $ssh->exec(RemoteOperation::GET_HOSTNAME->getPredefinedCommand()));

        // 7. Systemd Services Probe (Including Multi-PHP)
        $servicesRaw = (string) $ssh->exec(RemoteOperation::GET_SYSTEMD_SERVICES->getPredefinedCommand());
        $services = $this->systemdParser->parse($servicesRaw);

        return ServerDiscoveryData::fromArray([
            'os_name' => $osInfo['os_name'] ?? 'Linux',
            'os_version' => $osInfo['os_version'] ?? 'Unknown',
            'kernel_version' => $kernelRaw ?: 'Unknown',
            'architecture' => $archRaw ?: 'x86_64',
            'cpu_cores' => $cpuInfo['cpu_cores'] ?? 1,
            'total_ram' => $memInfo['total_ram'] ?? 0,
            'total_disk' => $diskInfo['total_disk'] ?? 0,
            'hostname' => $hostnameRaw ?: $server->hostname,
            'detected_services' => $services,
            'raw_metadata' => [
                'pretty_name' => $osInfo['pretty_name'] ?? 'Linux',
                'cpu_model' => $cpuInfo['cpu_model'] ?? 'Standard vCPU',
                'disk_usage_pct' => $diskInfo['disk_usage'] ?? 0,
            ]
        ]);
    }

    /**
     * Execute an allowlisted operation with strict parameter whitelisting.
     */
    public function executeAllowlistedOperation(Server $server, string $operation, array $params = []): array
    {
        $remoteOp = RemoteOperation::tryFrom($operation);
        if (!$remoteOp) {
            throw new ServerException("Operation '{$operation}' is not an approved RemoteOperation enum value.");
        }

        $command = $remoteOp->getPredefinedCommand($params);
        $ssh = $this->createSshConnection($server);

        $startTime = microtime(true);
        $output = (string) $ssh->exec($command);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $exitCode = $ssh->getExitStatus();

        if (strlen($output) > $this->maxOutputBytes) {
            $output = substr($output, 0, $this->maxOutputBytes) . "\n[OUTPUT TRUNCATED BY HOSTINGOS SECURITY LIMIT]";
        }

        return [
            'success' => ($exitCode === 0),
            'operation' => $operation,
            'output' => $output,
            'exit_code' => $exitCode,
            'duration_ms' => $durationMs,
        ];
    }
}
