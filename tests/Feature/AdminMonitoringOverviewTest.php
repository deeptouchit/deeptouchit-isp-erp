<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringOverviewTest extends TestCase
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

    public function test_admin_can_view_monitoring_overview_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.overview'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Monitoring/Overview')
            ->has('stats')
            ->has('cpu')
            ->has('memory')
            ->has('disk')
            ->has('network')
            ->has('services')
            ->has('processes')
            ->has('system')
        );
    }

    public function test_admin_can_drop_caches(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.drop-caches'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_poll_api_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.metrics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'cpu',
            'memory',
            'disk',
            'network',
            'services',
            'processes',
            'system',
        ]);
    }
}
