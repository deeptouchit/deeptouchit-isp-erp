<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringCpuTest extends TestCase
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

    public function test_admin_can_view_monitoring_cpu_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.cpu'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Monitoring/Cpu')
            ->has('stats')
            ->has('cores_data')
            ->has('hardware')
            ->has('processes')
        );
    }

    public function test_admin_can_poll_api_cpu_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.cpu'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'cores_data',
            'hardware',
            'processes',
        ]);
    }

    public function test_admin_can_renice_process(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.renice-process'), [
                'pid' => 999999, // non-existent dummy PID
                'nice' => 5,
            ]);

        $response->assertRedirect();
    }
}
