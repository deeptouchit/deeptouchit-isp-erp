<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringDatabaseTest extends TestCase
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

    public function test_admin_can_view_database_monitoring_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.database'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Monitoring/Database')
            ->has('stats')
            ->has('engines')
            ->has('processes')
            ->has('databases')
        );
    }

    public function test_admin_can_fetch_database_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.database'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'active_connections',
                'max_connections',
                'qps_rate',
                'innodb_hit_rate',
            ],
            'engines',
            'processes',
            'databases',
        ]);
    }

    public function test_admin_can_flush_database_tables(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.database.flush-tables'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_restart_database_service(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.database.restart'), [
                'engine' => 'mysql',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
