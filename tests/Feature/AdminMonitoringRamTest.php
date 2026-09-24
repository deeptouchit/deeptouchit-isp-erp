<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringRamTest extends TestCase
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

    public function test_admin_can_view_monitoring_ram_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.ram'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Monitoring/Ram')
            ->has('stats')
            ->has('breakdown')
            ->has('hardware')
            ->has('processes')
        );
    }

    public function test_admin_can_poll_api_ram_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.ram'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'breakdown',
            'hardware',
            'processes',
        ]);
    }

    public function test_admin_can_flush_swap(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.flush-swap'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
