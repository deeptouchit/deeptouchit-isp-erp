<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_services_monitoring_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.services'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Monitoring/Services')
            ->has('stats')
            ->has('services')
        );
    }

    public function test_admin_can_fetch_services_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.services'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_services',
                'active_services',
                'health_score',
            ],
            'services',
        ]);
    }

    public function test_admin_can_execute_service_action(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.services.action'), [
                'service' => 'cron',
                'action' => 'restart',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_fetch_service_journal_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.services.logs', ['service' => 'cron']));

        $response->assertOk();
        $response->assertJsonStructure([
            'service',
            'logs',
        ]);
    }
}
