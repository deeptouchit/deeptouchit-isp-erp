<?php

namespace Tests\Feature;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerCredentialStatus;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerLogLevel;
use App\Enums\Infrastructure\ServerLogType;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Models\Server;
use App\Models\ServerCredential;
use App\Models\ServerEvent;
use App\Models\ServerGroup;
use App\Models\ServerLog;
use App\Models\ServerMetric;
use App\Models\ServerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServerDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_can_be_created_with_auto_generated_uuid(): void
    {
        $group = ServerGroup::factory()->create();

        $server = Server::create([
            'name' => 'Cluster Worker 01',
            'hostname' => 'worker01.deeptouchhost.local',
            'ip_address' => '192.168.1.100',
            'server_group_id' => $group->id,
            'server_type' => ServerType::WORKER,
            'environment' => ServerEnvironment::PRODUCTION,
            'status' => ServerStatus::ACTIVE,
            'health_status' => ServerHealthStatus::HEALTHY,
            'auth_type' => ServerAuthType::PASSWORD,
            'encrypted_ssh_password' => 'SuperSecretRootPassword!',
        ]);

        $this->assertNotNull($server->id);
        $this->assertNotEmpty($server->uuid);
        $this->assertEquals('192.168.1.100', $server->primary_ip);
        $this->assertEquals(ServerStatus::ACTIVE, $server->status);
        $this->assertEquals(ServerHealthStatus::HEALTHY, $server->health_status);
        $this->assertEquals(ServerType::WORKER, $server->server_type);
        $this->assertEquals($group->id, $server->group->id);
    }

    public function test_server_credentials_are_encrypted_in_database_and_hidden_from_serialization(): void
    {
        $server = Server::factory()->create([
            'encrypted_ssh_password' => 'RawPasswordForTesting',
            'agent_token' => 'SecretAgentToken12345',
        ]);

        // 1. Raw DB check: must NOT equal plaintext
        $rawRow = DB::table('servers')->where('id', $server->id)->first();
        $this->assertNotEquals('RawPasswordForTesting', $rawRow->encrypted_ssh_password);

        // 2. Eloquent decryption check
        $freshServer = Server::find($server->id);
        $this->assertEquals('RawPasswordForTesting', $freshServer->encrypted_ssh_password);

        // 3. Serialization safety: never leak to array or json
        $array = $freshServer->toArray();
        $this->assertArrayNotHasKey('encrypted_ssh_password', $array);
        $this->assertArrayNotHasKey('encrypted_ssh_key', $array);
        $this->assertArrayNotHasKey('agent_token', $array);

        $json = json_encode($freshServer);
        $this->assertStringNotContainsString('RawPasswordForTesting', $json);
        $this->assertStringNotContainsString('SecretAgentToken12345', $json);
    }

    public function test_server_group_slug_and_uuid_auto_generation(): void
    {
        $group = ServerGroup::create([
            'name' => 'Dhaka Datacenter',
            'location' => 'Bangladesh',
        ]);

        $this->assertNotEmpty($group->uuid);
        $this->assertEquals('dhaka-datacenter', $group->slug);
        $this->assertEquals('active', $group->status);
    }

    public function test_server_metrics_relationship_and_numeric_casts(): void
    {
        $server = Server::factory()->create();

        $metric = ServerMetric::create([
            'server_id' => $server->id,
            'cpu_usage' => 45.75,
            'memory_total' => 16384,
            'memory_used' => 8192,
            'memory_available' => 8192,
            'disk_total' => 500,
            'disk_used' => 120,
            'disk_usage' => 24.00,
            'load_1m' => 0.65,
            'load_5m' => 0.50,
            'load_15m' => 0.40,
            'network_rx' => 1024000,
            'network_tx' => 512000,
            'recorded_at' => now(),
        ]);

        $this->assertEquals(45.75, $metric->cpu_usage);
        $this->assertEquals(16384, $metric->memory_total);
        $this->assertEquals($server->id, $metric->server->id);

        $this->assertCount(1, $server->metrics);
        $this->assertEquals($metric->id, $server->latestMetric->id);
    }

    public function test_server_services_relationship_and_status_enum(): void
    {
        $server = Server::factory()->create();

        $service = ServerService::create([
            'server_id' => $server->id,
            'service_name' => 'mysql',
            'display_name' => 'MySQL Database Engine',
            'service_type' => 'database',
            'status' => ServerServiceStatus::RUNNING,
            'version' => '8.0.36',
            'port' => 3306,
            'enabled' => true,
            'pid' => 1845,
            'cpu_usage' => 2.5,
            'memory_usage' => 256,
        ]);

        $this->assertEquals(ServerServiceStatus::RUNNING, $service->status);
        $this->assertTrue($service->enabled);
        $this->assertEquals($server->id, $service->server->id);

        $runningServices = ServerService::running()->get();
        $this->assertTrue($runningServices->contains('id', $service->id));
    }

    public function test_server_logs_and_events_persistence(): void
    {
        $server = Server::factory()->create();

        $log = ServerLog::create([
            'server_id' => $server->id,
            'log_type' => ServerLogType::SYSTEM,
            'level' => ServerLogLevel::WARNING,
            'message' => 'High memory threshold reached',
            'context' => ['memory_percent' => 88.5],
            'occurred_at' => now(),
        ]);

        $this->assertEquals(ServerLogType::SYSTEM, $log->log_type);
        $this->assertEquals(ServerLogLevel::WARNING, $log->level);
        $this->assertIsArray($log->context);

        $event = ServerEvent::create([
            'server_id' => $server->id,
            'event_type' => ServerEventType::SERVER_MAINTENANCE_STARTED,
            'severity' => ServerEventSeverity::WARNING,
            'message' => 'Node entered maintenance mode',
            'occurred_at' => now(),
        ]);

        $this->assertEquals(ServerEventType::SERVER_MAINTENANCE_STARTED, $event->event_type);
        $this->assertNull($event->resolved_at);

        $unresolvedEvents = ServerEvent::unresolved()->get();
        $this->assertTrue($unresolvedEvents->contains('id', $event->id));
    }

    public function test_server_credential_model_stores_encrypted_secrets_and_hides_them(): void
    {
        $server = Server::factory()->create();

        $credential = ServerCredential::create([
            'server_id' => $server->id,
            'credential_type' => ServerCredentialType::SSH_PRIVATE_KEY,
            'name' => 'Ansible Automation Deploy Key',
            'username' => 'root',
            'encrypted_secret' => '-----BEGIN OPENSSH PRIVATE KEY-----MIIEpQIBAAKCAQEA...',
            'fingerprint' => 'SHA256:abc123456789',
            'status' => ServerCredentialStatus::ACTIVE,
        ]);

        // 1. Raw DB check
        $rawCred = DB::table('server_credentials')->where('id', $credential->id)->first();
        $this->assertNotEquals('-----BEGIN OPENSSH PRIVATE KEY-----MIIEpQIBAAKCAQEA...', $rawCred->encrypted_secret);

        // 2. Eloquent Decryption
        $freshCred = ServerCredential::find($credential->id);
        $this->assertEquals('-----BEGIN OPENSSH PRIVATE KEY-----MIIEpQIBAAKCAQEA...', $freshCred->encrypted_secret);

        // 3. Serialization security
        $array = $freshCred->toArray();
        $this->assertArrayNotHasKey('encrypted_secret', $array);

        $json = json_encode($freshCred);
        $this->assertStringNotContainsString('BEGIN OPENSSH PRIVATE KEY', $json);
    }

    public function test_server_query_scopes(): void
    {
        Server::factory()->create(['status' => ServerStatus::ONLINE, 'is_master' => true]);
        Server::factory()->create(['status' => ServerStatus::OFFLINE, 'is_master' => false]);
        Server::factory()->create(['status' => ServerStatus::MAINTENANCE, 'is_master' => false]);

        $this->assertCount(1, Server::online()->get());
        $this->assertCount(1, Server::offline()->get());
        $this->assertCount(1, Server::maintenance()->get());
        $this->assertCount(1, Server::master()->get());
        $this->assertCount(2, Server::workers()->get());
    }
}
