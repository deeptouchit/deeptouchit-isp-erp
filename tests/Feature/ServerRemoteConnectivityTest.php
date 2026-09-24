<?php

namespace Tests\Feature;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\Servers\RemoteOperation;
use App\Exceptions\Infrastructure\Servers\ServerException;
use App\Exceptions\Infrastructure\Servers\ServerVerificationException;
use App\Models\Server;
use App\Models\ServerEvent;
use App\Services\Infrastructure\Servers\Parsers\CpuInfoParser;
use App\Services\Infrastructure\Servers\Parsers\DiskInfoParser;
use App\Services\Infrastructure\Servers\Parsers\LoadAvgParser;
use App\Services\Infrastructure\Servers\Parsers\MemInfoParser;
use App\Services\Infrastructure\Servers\Parsers\NetworkInterfacesParser;
use App\Services\Infrastructure\Servers\Parsers\OsReleaseParser;
use App\Services\Infrastructure\Servers\Parsers\SystemdServicesParser;
use App\Services\Infrastructure\Servers\SshRemoteServerClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerRemoteConnectivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_os_release_parser(): void
    {
        $raw = <<<EOT
NAME="Ubuntu"
VERSION="24.04 LTS (Noble Numbat)"
ID=ubuntu
ID_LIKE=debian
PRETTY_NAME="Ubuntu 24.04 LTS"
VERSION_ID="24.04"
EOT;
        $parser = new OsReleaseParser();
        $data = $parser->parse($raw);

        $this->assertEquals('Ubuntu', $data['os_name']);
        $this->assertEquals('24.04', $data['os_version']);
        $this->assertEquals('Ubuntu 24.04 LTS', $data['pretty_name']);
        $this->assertEquals('ubuntu', $data['id']);
    }

    public function test_cpu_info_parser(): void
    {
        $raw = <<<EOT
processor	: 0
model name	: AMD EPYC 7763 64-Core Processor
cpu cores	: 4

processor	: 1
model name	: AMD EPYC 7763 64-Core Processor
cpu cores	: 4
EOT;
        $parser = new CpuInfoParser();
        $data = $parser->parse($raw);

        $this->assertEquals(2, $data['cpu_cores']);
        $this->assertEquals('AMD EPYC 7763 64-Core Processor', $data['cpu_model']);
    }

    public function test_mem_info_parser(): void
    {
        $raw = <<<EOT
MemTotal:       16384000 kB
MemFree:         4096000 kB
MemAvailable:    8192000 kB
Buffers:          500000 kB
Cached:          3000000 kB
EOT;
        $parser = new MemInfoParser();
        $data = $parser->parse($raw);

        $this->assertEquals(16000, $data['total_ram']);
        $this->assertEquals(8000, $data['available_ram']);
        $this->assertEquals(8000, $data['used_ram']);
    }

    public function test_disk_info_parser(): void
    {
        $raw = <<<EOT
Filesystem     1B-blocks         Used    Available Use% Mounted on
/dev/sda1   536870912000 107374182400 429496729600  20% /
EOT;
        $parser = new DiskInfoParser();
        $data = $parser->parse($raw);

        $this->assertEquals(500, $data['total_disk']);
        $this->assertEquals(100, $data['used_disk']);
        $this->assertEquals(20.0, $data['disk_usage']);
    }

    public function test_systemd_services_parser_matches_multi_php_and_hosting_daemons(): void
    {
        $raw = <<<EOT
  nginx.service          loaded active running A high performance web server
  mysql.service          loaded active running MySQL Community Server
  php8.1-fpm.service     loaded active running The PHP 8.1 FastCGI Process Manager
  php8.3-fpm.service     loaded active running The PHP 8.3 FastCGI Process Manager
  fail2ban.service       loaded active running Fail2ban Service
  unrelated-app.service  loaded active running Custom Unrelated Service
EOT;
        $parser = new SystemdServicesParser();
        $services = $parser->parse($raw);

        $this->assertContains('nginx', $services);
        $this->assertContains('mysql', $services);
        $this->assertContains('php8.1-fpm', $services);
        $this->assertContains('php8.3-fpm', $services);
        $this->assertContains('fail2ban', $services);
        $this->assertNotContains('unrelated-app', $services);
    }

    public function test_network_interfaces_parser(): void
    {
        $raw = <<<EOT
1: lo    inet 127.0.0.1/8 scope host lo\       valid_lft forever preferred_lft forever
2: eth0    inet 192.168.1.50/24 brd 192.168.1.255 scope global dynamic eth0\       valid_lft 86175sec preferred_lft 86175sec
2: eth0    inet6 2001:db8::1/64 scope global\       valid_lft forever preferred_lft forever
EOT;
        $parser = new NetworkInterfacesParser();
        $interfaces = $parser->parse($raw);

        $this->assertCount(2, $interfaces);
        $eth0 = collect($interfaces)->firstWhere('interface', 'eth0');
        $this->assertNotNull($eth0);
        $this->assertContains('192.168.1.50', $eth0['ipv4']);
        $this->assertContains('2001:db8::1', $eth0['ipv6']);
    }

    public function test_load_avg_parser(): void
    {
        $raw = "0.75 0.50 0.25 1/120 12345\n86400.12 72000.55";
        $parser = new LoadAvgParser();
        $data = $parser->parse($raw);

        $this->assertEquals(0.75, $data['load_1m']);
        $this->assertEquals(0.50, $data['load_5m']);
        $this->assertEquals(0.25, $data['load_15m']);
        $this->assertEquals(86400, $data['uptime_seconds']);
    }

    public function test_command_injection_and_unauthorized_operations_rejected(): void
    {
        // 1. Invalid operation not in enum
        $this->assertNull(RemoteOperation::tryFrom('malicious_rm_rf'));

        // 2. Shell injection attempt inside service name is rejected with ServerException
        $this->expectException(ServerException::class);
        RemoteOperation::SYSTEMD_RESTART->getPredefinedCommand([
            'service' => 'nginx; rm -rf /',
        ]);
    }

    public function test_host_key_fingerprint_strict_verification_mismatch_triggers_security_event(): void
    {
        $server = Server::factory()->create([
            'trusted_ssh_host_key_fingerprint' => 'SHA256:TRUSTED_KEY_AAA_123',
            'ssh_host_key_policy' => 'strict',
        ]);

        $client = app(SshRemoteServerClient::class);

        // Reflection to test protected verifyHostKeyPolicy
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('verifyHostKeyPolicy');
        $method->setAccessible(true);

        $this->expectException(ServerVerificationException::class);
        $this->expectExceptionMessage('SSH host key verification failed');

        $method->invoke($client, $server, 'SHA256:ATTACKER_KEY_XYZ_999');

        // Verify security event created
        $this->assertDatabaseHas('server_events', [
            'server_id' => $server->id,
            'event_type' => ServerEventType::SERVER_OFFLINE->value,
            'severity' => ServerEventSeverity::CRITICAL->value,
        ]);
    }

    public function test_host_key_tofu_policy_records_first_fingerprint(): void
    {
        $server = Server::factory()->create([
            'trusted_ssh_host_key_fingerprint' => null,
            'ssh_host_key_fingerprint' => null,
            'ssh_host_key_policy' => 'tofu',
        ]);

        $client = app(SshRemoteServerClient::class);
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('verifyHostKeyPolicy');
        $method->setAccessible(true);

        $method->invoke($client, $server, 'SHA256:INITIAL_TRUSTED_KEY_456');

        $this->assertEquals('SHA256:INITIAL_TRUSTED_KEY_456', $server->fresh()->ssh_host_key_fingerprint);
        $this->assertEquals('SHA256:INITIAL_TRUSTED_KEY_456', $server->fresh()->trusted_ssh_host_key_fingerprint);
    }

    public function test_agent_push_telemetry_authentication_and_rate_limiting(): void
    {
        $server = Server::factory()->create(['agent_token' => 'sh_agt_test_token_123456789']);

        // 1. Unauthenticated request -> 401
        $response = $this->postJson('/api/v1/agent/telemetry', []);
        $response->assertStatus(401)
            ->assertJson(['error' => 'AGENT_UNAUTHENTICATED']);

        // 2. Invalid Token -> 401
        $response = $this->withHeaders(['Authorization' => 'Bearer sh_agt_invalid_token'])
            ->postJson('/api/v1/agent/telemetry', []);
        $response->assertStatus(401)
            ->assertJson(['error' => 'AGENT_TOKEN_INVALID']);

        // 3. Valid Token with Telemetry -> 200
        $telemetry = [
            'cpu_usage' => 18.5,
            'memory_total' => 16384,
            'memory_used' => 4096,
            'disk_total' => 250,
            'disk_used' => 50,
            'load_1m' => 0.45,
            'load_5m' => 0.40,
            'load_15m' => 0.35,
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer sh_agt_test_token_123456789'])
            ->postJson('/api/v1/agent/telemetry', [
                'agent_version' => 'v1.4.2',
                'metrics' => $telemetry,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('v1.4.2', $server->fresh()->agent_version);
        $this->assertEquals(18.5, $server->fresh()->latestMetric->cpu_usage);
    }

    public function test_agent_push_nonce_replay_protection(): void
    {
        $server = Server::factory()->create(['agent_token' => 'sh_agt_nonce_test_token']);
        $timestamp = (string) time();
        $nonce = 'unique_nonce_abc_123';

        $headers = [
            'Authorization' => 'Bearer sh_agt_nonce_test_token',
            'X-Agent-Timestamp' => $timestamp,
            'X-Agent-Nonce' => $nonce,
        ];

        // First request succeeds
        $res1 = $this->withHeaders($headers)->postJson('/api/v1/agent/telemetry', ['cpu_usage' => 10]);
        $res1->assertStatus(200);

        // Second request with same nonce is rejected (replay attack)
        $res2 = $this->withHeaders($headers)->postJson('/api/v1/agent/telemetry', ['cpu_usage' => 10]);
        $res2->assertStatus(403)
            ->assertJson(['error' => 'AGENT_REPLAY_DETECTED']);
    }

    public function test_agent_push_hmac_signature_verification(): void
    {
        $token = 'sh_agt_hmac_secret_token';
        $server = Server::factory()->create(['agent_token' => $token]);

        $timestamp = (string) time();
        $nonce = 'nonce_xyz_789';
        $body = json_encode(['cpu_usage' => 25.0]);

        $payload = ['cpu_usage' => 25.0];
        $body = json_encode($payload);

        // 1. Invalid signature -> 403
        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Agent-Timestamp' => $timestamp,
            'X-Agent-Nonce' => $nonce,
            'X-Agent-Signature' => 'invalid_signature_hash',
        ])->postJson('/api/v1/agent/telemetry', $payload)
          ->assertStatus(403)
          ->assertJson(['error' => 'AGENT_SIGNATURE_INVALID']);

        // 2. Valid signature -> 200
        $validNonce = $nonce . '_second';
        $validSignature = hash_hmac('sha256', $timestamp . '.' . $validNonce . '.' . $body, $token);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Agent-Timestamp' => $timestamp,
            'X-Agent-Nonce' => $validNonce,
            'X-Agent-Signature' => $validSignature,
        ])->postJson('/api/v1/agent/telemetry', $payload)
          ->assertStatus(200)
          ->assertJson(['success' => true]);
    }
}
