<?php

namespace Tests\Feature;

use App\Models\MonitoringAlertState;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->server = Server::create([
            'name' => 'Primary Node',
            'hostname' => 'primary.deeptouchhost.local',
            'ip_address' => '103.59.177.138',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
        ]);
    }

    public function test_admin_can_view_alerts_monitoring_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.alerts'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Monitoring/Alerts')
            ->has('stats')
            ->has('incidents')
            ->has('history')
            ->has('rules')
            ->has('channels')
        );
    }

    public function test_admin_can_fetch_alerts_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.alerts'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'active_critical',
                'active_warning',
                'total_rules',
                'health_score',
            ],
            'incidents',
            'history',
            'rules',
            'channels',
        ]);
    }

    public function test_admin_can_save_alert_rule(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.alerts.rules.save'), [
                'alert_type' => 'cpu_high',
                'warning_threshold' => 75.0,
                'critical_threshold' => 90.0,
                'duration_seconds' => 60,
                'cooldown_seconds' => 300,
                'enabled' => true,
                'channels' => ['email', 'webhook'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('monitoring_alert_rules', [
            'alert_type' => 'cpu_high',
            'warning_threshold' => 75.0,
            'critical_threshold' => 90.0,
        ]);
    }

    public function test_admin_can_resolve_and_suppress_alert(): void
    {
        $alert = MonitoringAlertState::create([
            'server_id' => $this->server->id,
            'alert_type' => 'cpu_high',
            'resource_identity' => 'global',
            'state' => 'critical',
            'severity' => 'critical',
            'current_value' => '98.5%',
            'threshold_value' => '95.0%',
            'fingerprint' => 'test-fingerprint',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.alerts.suppress'), [
                'alert_id' => $alert->id,
                'duration' => 60,
                'reason' => 'Maintenance test',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.alerts.resolve'), [
                'alert_id' => $alert->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_dispatch_test_notification(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.monitoring.alerts.test-notification'), [
                'channel' => 'email',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
