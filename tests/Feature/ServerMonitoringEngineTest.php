<?php

namespace Tests\Feature;

use App\Enums\Infrastructure\MonitoringAlertStatus;
use App\Enums\Infrastructure\MonitoringAlertType;
use App\Enums\Infrastructure\ServerStatus;
use App\Jobs\Infrastructure\Monitoring\EvaluateServerHeartbeatJob;
use App\Jobs\Infrastructure\Monitoring\ProcessMonitoringTelemetryJob;
use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Models\ServerMetricAggregate;
use App\Models\User;
use App\Services\Infrastructure\Monitoring\MetricAggregationService;
use App\Services\Infrastructure\Monitoring\MetricRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerMonitoringEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@deeptouchhost.local',
        ]);

        $this->server = Server::factory()->create([
            'name' => 'Telemetry Node 01',
            'hostname' => 'telemetry01.deeptouchhost.io',
            'ip_address' => '192.168.10.101',
            'agent_token' => 'sh_agt_monitoring_token_123',
            'status' => ServerStatus::ACTIVE,
        ]);
    }

    public function test_telemetry_ingestion_normalizes_and_persists_metrics(): void
    {
        $payload = [
            'timestamp' => now()->toIso8601String(),
            'cpu' => [
                'usage_percent' => 45.5,
                'load_1m' => 1.25,
                'load_5m' => 1.10,
                'load_15m' => 0.95,
            ],
            'memory' => [
                'total_bytes' => 16384,
                'used_bytes' => 8192,
            ],
            'disk' => [
                [
                    'mount' => '/',
                    'total_bytes' => 102400,
                    'used_bytes' => 40960,
                ]
            ],
            'network' => [
                [
                    'interface' => 'eth0',
                    'rx_bytes' => 500000,
                    'tx_bytes' => 250000,
                ]
            ],
            'services' => [
                ['name' => 'nginx', 'state' => 'running'],
                ['name' => 'php8.4-fpm', 'state' => 'running'],
            ]
        ];

        // Process telemetry job synchronously
        ProcessMonitoringTelemetryJob::dispatchSync($this->server->id, $payload, '1.4.0');

        $this->server->refresh();
        $this->assertEquals(16384, $this->server->total_ram);
        $this->assertEquals(8192, $this->server->used_ram);
        $this->assertEquals(1.25, $this->server->load_avg_1min);
        $this->assertEquals('1.4.0', $this->server->agent_version);

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $this->server->id,
            'cpu_usage' => 45.5,
            'memory_used' => 8192,
        ]);

        $this->assertDatabaseHas('server_services', [
            'server_id' => $this->server->id,
            'service_name' => 'nginx',
            'status' => 'running',
        ]);
    }

    public function test_alert_rule_engine_creates_warning_and_escalates_to_critical(): void
    {
        // 1. Send warning level CPU telemetry (80% >= 75%)
        $warningPayload = [
            'cpu' => ['usage_percent' => 80.0],
            'memory' => ['total_bytes' => 10000, 'used_bytes' => 5000],
            'disk' => [['mount' => '/', 'total_bytes' => 10000, 'used_bytes' => 3000]],
            'services' => [],
        ];

        ProcessMonitoringTelemetryJob::dispatchSync($this->server->id, $warningPayload);

        $alert = MonitoringAlertState::where('server_id', $this->server->id)
            ->where('alert_type', MonitoringAlertType::CPU_HIGH)
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals(MonitoringAlertStatus::WARNING, $alert->state);
        $this->assertEquals('80%', $alert->current_value);

        // 2. Send critical level CPU telemetry (95% >= 90%) -> Escalation
        $criticalPayload = [
            'cpu' => ['usage_percent' => 95.0],
            'memory' => ['total_bytes' => 10000, 'used_bytes' => 5000],
            'disk' => [['mount' => '/', 'total_bytes' => 10000, 'used_bytes' => 3000]],
            'services' => [],
        ];

        ProcessMonitoringTelemetryJob::dispatchSync($this->server->id, $criticalPayload);

        $alert->refresh();
        $this->assertEquals(MonitoringAlertStatus::CRITICAL, $alert->state);
        $this->assertEquals('95%', $alert->current_value);
        $this->assertNotNull($alert->last_escalated_at);

        // 3. Send normal CPU telemetry (40% < 75%) -> Recovery
        $recoveredPayload = [
            'cpu' => ['usage_percent' => 40.0],
            'memory' => ['total_bytes' => 10000, 'used_bytes' => 5000],
            'disk' => [['mount' => '/', 'total_bytes' => 10000, 'used_bytes' => 3000]],
            'services' => [],
        ];

        ProcessMonitoringTelemetryJob::dispatchSync($this->server->id, $recoveredPayload);

        $alert->refresh();
        $this->assertEquals(MonitoringAlertStatus::RECOVERED, $alert->state);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_heartbeat_job_marks_delayed_servers_offline_and_recovers(): void
    {
        // 1. Simulate server with last_seen_at 400 seconds ago (> 300s offline threshold)
        $this->server->update([
            'last_seen_at' => now()->subSeconds(400),
            'status' => ServerStatus::ACTIVE,
        ]);

        app(EvaluateServerHeartbeatJob::class)->handle(
            app(\App\Services\Infrastructure\Servers\ServerEventService::class),
            app(\App\Services\Infrastructure\Monitoring\MonitoringNotificationDispatcher::class)
        );

        $this->server->refresh();
        $this->assertEquals(ServerStatus::OFFLINE, $this->server->status);

        $this->assertDatabaseHas('monitoring_alert_states', [
            'server_id' => $this->server->id,
            'alert_type' => MonitoringAlertType::SERVER_OFFLINE->value,
            'state' => MonitoringAlertStatus::CRITICAL->value,
        ]);

        // 2. Server sends heartbeat -> recovers
        $this->server->update([
            'last_seen_at' => now(),
            'status' => ServerStatus::ONLINE,
        ]);

        app(EvaluateServerHeartbeatJob::class)->handle(
            app(\App\Services\Infrastructure\Servers\ServerEventService::class),
            app(\App\Services\Infrastructure\Monitoring\MonitoringNotificationDispatcher::class)
        );

        $this->assertDatabaseHas('monitoring_alert_states', [
            'server_id' => $this->server->id,
            'alert_type' => MonitoringAlertType::SERVER_OFFLINE->value,
            'state' => MonitoringAlertStatus::RECOVERED->value,
        ]);
    }

    public function test_metric_aggregation_and_retention_services(): void
    {
        // 1. Insert metric samples
        $hourStart = now()->subHour()->startOfHour();
        ServerMetric::create([
            'server_id' => $this->server->id,
            'cpu_usage' => 40.0,
            'memory_total' => 16384,
            'memory_used' => 8000,
            'disk_total' => 100000,
            'disk_used' => 45000,
            'disk_usage' => 45.0,
            'load_1m' => 1.2,
            'recorded_at' => $hourStart->copy()->addMinutes(10),
        ]);

        ServerMetric::create([
            'server_id' => $this->server->id,
            'cpu_usage' => 60.0,
            'memory_total' => 16384,
            'memory_used' => 8000,
            'disk_total' => 100000,
            'disk_used' => 45000,
            'disk_usage' => 45.0,
            'load_1m' => 1.8,
            'recorded_at' => $hourStart->copy()->addMinutes(30),
        ]);

        // 2. Run hourly aggregation
        $aggregationService = app(MetricAggregationService::class);
        $aggregationService->aggregateHourly($hourStart);

        $aggregate = ServerMetricAggregate::where('server_id', $this->server->id)
            ->where('interval', '1h')
            ->where('period_start', $hourStart)
            ->first();

        $this->assertNotNull($aggregate);
        $this->assertEquals(50.0, $aggregate->cpu_avg);
        $this->assertEquals(40.0, $aggregate->cpu_min);
        $this->assertEquals(60.0, $aggregate->cpu_max);
        $this->assertEquals(2, $aggregate->sample_count);

        // 3. Test retention pruning
        $oldMetric = ServerMetric::create([
            'server_id' => $this->server->id,
            'cpu_usage' => 20.0,
            'recorded_at' => now()->subDays(10),
        ]);

        $retentionService = app(MetricRetentionService::class);
        $deletedCount = $retentionService->pruneExpiredRawMetrics();

        $this->assertGreaterThanOrEqual(1, $deletedCount);
        $this->assertDatabaseMissing('server_metrics', ['id' => $oldMetric->id]);
    }

    public function test_monitoring_api_endpoints(): void
    {
        Sanctum::actingAs($this->adminUser);

        // 1. Create a metric sample
        ServerMetric::create([
            'server_id' => $this->server->id,
            'cpu_usage' => 33.3,
            'memory_total' => 16384,
            'memory_used' => 6000,
            'disk_total' => 100000,
            'disk_used' => 30000,
            'disk_usage' => 30.0,
            'load_1m' => 0.85,
            'recorded_at' => now(),
        ]);

        // 2. Test Snapshot API
        $this->getJson("/api/v1/servers/{$this->server->id}/monitoring")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'server' => ['id', 'uuid', 'name', 'status', 'health_status'],
                    'current' => ['cpu_usage', 'load_1m', 'memory_used', 'disk_used'],
                ]
            ]);

        // 3. Test History API
        $this->getJson("/api/v1/servers/{$this->server->id}/monitoring/history?range=1h")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'timestamp', 'cpu', 'ram', 'disk', 'load_1m']
                ]
            ]);

        // 4. Test Alerts API
        $this->getJson("/api/v1/servers/{$this->server->id}/monitoring/alerts")
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        // 5. Test Test Alert Endpoint
        $this->postJson("/api/v1/servers/{$this->server->id}/monitoring/test-alert")
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
