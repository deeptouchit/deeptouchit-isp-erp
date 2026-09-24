<?php

namespace Tests\Feature;

use App\Contracts\Infrastructure\Servers\RemoteServerClientInterface;
use App\Data\Infrastructure\Servers\ServerMetricsData;
use App\Enums\Infrastructure\ServerCredentialStatus;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerServiceStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Exceptions\Infrastructure\Servers\ServerException;
use App\Exceptions\Infrastructure\Servers\ServerLifecycleException;
use App\Exceptions\Infrastructure\Servers\ServerMetricsValidationException;
use App\Jobs\Infrastructure\Servers\CollectServerMetricsJob;
use App\Jobs\Infrastructure\Servers\DiscoverServerJob;
use App\Jobs\Infrastructure\Servers\HealthCheckServerJob;
use App\Jobs\Infrastructure\Servers\SyncServerJob;
use App\Jobs\Infrastructure\Servers\VerifyServerJob;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\ServerService as ServerServiceModel;
use App\Services\Infrastructure\Servers\FakeRemoteServerClient;
use App\Services\Infrastructure\Servers\ServerAgentService;
use App\Services\Infrastructure\Servers\ServerCredentialService;
use App\Services\Infrastructure\Servers\ServerDiscoveryService;
use App\Services\Infrastructure\Servers\ServerHealthService;
use App\Services\Infrastructure\Servers\ServerLifecycleService;
use App\Services\Infrastructure\Servers\ServerMetricsService;
use App\Services\Infrastructure\Servers\ServerService;
use App\Services\Infrastructure\Servers\ServerServiceManager;
use App\Services\Infrastructure\Servers\ServerVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServerDomainServicesTest extends TestCase
{
    use RefreshDatabase;

    protected FakeRemoteServerClient $fakeClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeClient = new FakeRemoteServerClient();
        $this->app->instance(RemoteServerClientInterface::class, $this->fakeClient);
    }

    public function test_server_lifecycle_service_valid_and_invalid_state_transitions(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::PENDING]);
        $lifecycle = app(ServerLifecycleService::class);

        // 1. Valid transition: PENDING -> VERIFYING -> VERIFIED -> ACTIVE
        $lifecycle->transition($server, ServerStatus::VERIFYING);
        $this->assertEquals(ServerStatus::VERIFYING, $server->fresh()->status);

        $lifecycle->transition($server, ServerStatus::VERIFIED);
        $this->assertEquals(ServerStatus::VERIFIED, $server->fresh()->status);

        $lifecycle->transition($server, ServerStatus::ACTIVE);
        $this->assertEquals(ServerStatus::ACTIVE, $server->fresh()->status);

        // 2. Invalid transition: ACTIVE -> PENDING (must throw ServerLifecycleException)
        $this->expectException(ServerLifecycleException::class);
        $lifecycle->transition($server, ServerStatus::PENDING);
    }

    public function test_server_lifecycle_service_maintenance_mode(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::ACTIVE]);
        $lifecycle = app(ServerLifecycleService::class);

        $lifecycle->transition($server, ServerStatus::MAINTENANCE, 'Security updates');
        $fresh = $server->fresh();
        $this->assertEquals(ServerStatus::MAINTENANCE, $fresh->status);
        $this->assertNotNull($fresh->maintenance_at);
        $this->assertEquals('Security updates', $fresh->maintenance_reason);

        // Back to active
        $lifecycle->transition($server, ServerStatus::ACTIVE);
        $this->assertEquals(ServerStatus::ACTIVE, $server->fresh()->status);
        $this->assertNull($server->fresh()->maintenance_at);
    }

    public function test_server_verification_service_success_and_failure(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::PENDING]);
        $verifier = app(ServerVerificationService::class);

        // 1. Successful verification
        $result = $verifier->verify($server);
        $this->assertTrue($result->success);
        $this->assertEquals(ServerStatus::VERIFIED, $server->fresh()->status);
        $this->assertNotNull($server->fresh()->last_ping_at);

        // 2. Failure verification
        $this->fakeClient->setShouldSucceed(false, 'Connection refused on port 22', 'SERVER_CONNECTION_REFUSED');
        $failedResult = $verifier->verify($server);
        $this->assertFalse($failedResult->success);
        $this->assertEquals(ServerStatus::OFFLINE, $server->fresh()->status);
    }

    public function test_server_discovery_service_populates_specs_and_services(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::VERIFIED]);
        $discoverer = app(ServerDiscoveryService::class);

        $discoveryData = $discoverer->discover($server);

        $fresh = $server->fresh();
        $this->assertEquals('Ubuntu', $fresh->os_name);
        $this->assertEquals(8, $fresh->cpu_cores);
        $this->assertEquals(16384, $fresh->total_ram);
        $this->assertEquals(320, $fresh->total_disk);

        // Check discovered services
        $nginxService = ServerServiceModel::where('server_id', $server->id)->where('service_name', 'nginx')->first();
        $this->assertNotNull($nginxService);
        $this->assertEquals(ServerServiceStatus::RUNNING, $nginxService->status);
    }

    public function test_server_health_service_evaluates_nominal_and_warning_states(): void
    {
        $server = Server::factory()->create([
            'status' => ServerStatus::ACTIVE,
            'total_ram' => 16384,
            'used_ram' => 4096,
            'total_disk' => 500,
            'used_disk' => 100,
        ]);
        $healthService = app(ServerHealthService::class);

        // 1. Nominal Healthy Check
        $result = $healthService->checkHealth($server);
        $this->assertEquals(ServerHealthStatus::HEALTHY, $result->status);
        $this->assertTrue($result->isOnline);

        // 2. Warning Check (High RAM: >90%)
        $server->update(['used_ram' => 15500]); // > 90%
        $warningResult = $healthService->checkHealth($server);
        $this->assertEquals(ServerHealthStatus::WARNING, $warningResult->status);
        $this->assertTrue($warningResult->ramWarning);
    }

    public function test_server_metrics_service_ingests_and_validates_telemetry(): void
    {
        $server = Server::factory()->create();
        $metricsService = app(ServerMetricsService::class);

        // 1. Valid telemetry ingestion
        $payload = [
            'cpu_usage' => 45.2,
            'memory_total' => 16384,
            'memory_used' => 8192,
            'disk_total' => 500,
            'disk_used' => 200,
            'load_1m' => 1.25,
            'load_5m' => 1.10,
            'load_15m' => 0.95,
        ];

        $metric = $metricsService->recordMetrics($server, $payload);
        $this->assertNotNull($metric->id);
        $this->assertEquals(45.2, $metric->cpu_usage);
        $this->assertEquals(1.25, $server->fresh()->load_avg_1min);

        // 2. Invalid CPU usage (> 100%) throws ServerMetricsValidationException
        $this->expectException(ServerMetricsValidationException::class);
        $metricsService->recordMetrics($server, ['cpu_usage' => 150.0]);
    }

    public function test_server_agent_service_token_lifecycle(): void
    {
        $server = Server::factory()->create();
        $agentService = app(ServerAgentService::class);

        $token = $agentService->generateAgentToken($server);
        $this->assertStringStartsWith('sh_agt_', $token);
        $this->assertNotNull($server->fresh()->agent_token);

        // Authenticate agent
        $authenticatedServer = $agentService->authenticateAgent($token);
        $this->assertNotNull($authenticatedServer);
        $this->assertEquals($server->id, $authenticatedServer->id);

        // Record heartbeat
        $agentService->recordHeartbeat($server, 'v1.2.0');
        $this->assertEquals('v1.2.0', $server->fresh()->agent_version);
    }

    public function test_server_credential_service_rotation_and_revocation(): void
    {
        $server = Server::factory()->create();
        $credService = app(ServerCredentialService::class);

        $cred = $credService->storeCredential(
            $server,
            ServerCredentialType::SSH_PASSWORD,
            'Root Pass',
            'root',
            'InitialSecret123!'
        );

        $this->assertEquals(ServerCredentialStatus::ACTIVE, $cred->status);
        $this->assertEquals('InitialSecret123!', $cred->encrypted_secret);

        // Rotate
        $rotated = $credService->rotateCredential($cred, 'NewSecret456!');
        $this->assertEquals('NewSecret456!', $rotated->encrypted_secret);

        // Revoke
        $credService->revokeCredential($rotated);
        $this->assertEquals(ServerCredentialStatus::REVOKED, $rotated->fresh()->status);
    }

    public function test_server_service_manager_manages_allowlisted_services_and_rejects_untrusted(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::ACTIVE]);
        $serviceManager = app(ServerServiceManager::class);

        // 1. Allowlisted service management
        $result = $serviceManager->manageService($server, 'nginx', 'restart');
        $this->assertTrue($result['success']);

        $service = ServerServiceModel::where('server_id', $server->id)->where('service_name', 'nginx')->first();
        $this->assertEquals(ServerServiceStatus::RUNNING, $service->status);

        // 2. Reject untrusted service name
        $this->expectException(ServerException::class);
        $serviceManager->manageService($server, 'unauthorized_daemon', 'start');
    }

    public function test_server_facade_service_register_update_and_protection(): void
    {
        $group = ServerGroup::factory()->create();
        $serverService = app(ServerService::class);

        // 1. Register server
        $server = $serverService->registerServer([
            'name' => 'Edge Worker 01',
            'hostname' => 'edge01.deeptouchhost.local',
            'ip_address' => '10.0.0.15',
            'server_group_id' => $group->id,
            'server_type' => ServerType::WORKER,
            'ssh_password' => 'SecurePass987!',
        ]);

        $this->assertNotNull($server->id);
        $this->assertEquals(ServerStatus::PENDING, $server->status);

        // 2. Update server
        $updated = $serverService->updateServer($server, [
            'name' => 'Edge Worker 01 (Updated)',
        ]);
        $this->assertEquals('Edge Worker 01 (Updated)', $updated->name);

        // 3. Deleting master node is strictly protected
        $master = Server::factory()->create(['is_master' => true]);
        $this->expectException(\RuntimeException::class);
        $serverService->deleteServer($master);
    }

    public function test_async_server_jobs_dispatching_and_execution(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::PENDING]);

        // Verify job execution
        $verifyJob = new VerifyServerJob($server);
        app()->call([$verifyJob, 'handle']);
        $this->assertEquals(ServerStatus::VERIFIED, $server->fresh()->status);

        // Discover job execution
        $discoverJob = new DiscoverServerJob($server);
        app()->call([$discoverJob, 'handle']);
        $this->assertEquals('Ubuntu', $server->fresh()->os_name);

        // Health check job execution
        $healthJob = new HealthCheckServerJob($server);
        app()->call([$healthJob, 'handle']);
        $this->assertNotNull($server->fresh()->last_health_check_at);

        // Metrics job execution
        $metricsJob = new CollectServerMetricsJob($server, [
            'cpu_usage' => 22.5,
            'memory_total' => 8192,
            'memory_used' => 2048,
            'disk_total' => 160,
            'disk_used' => 40,
            'load_1m' => 0.35,
        ]);
        app()->call([$metricsJob, 'handle']);
        $this->assertEquals(22.5, $server->fresh()->latestMetric->cpu_usage);

        // Full sync job execution
        $syncJob = new SyncServerJob($server);
        app()->call([$syncJob, 'handle']);
        $this->assertEquals(ServerHealthStatus::HEALTHY, $server->fresh()->health_status);
    }
}
