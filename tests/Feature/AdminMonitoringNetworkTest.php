<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringNetworkTest extends TestCase
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

    public function test_admin_can_view_monitoring_network_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.network'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Monitoring/Network')
            ->has('stats')
            ->has('interfaces')
            ->has('sockets')
            ->has('connections')
        );
    }

    public function test_admin_can_poll_api_network_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.network'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'interfaces',
            'sockets',
            'connections',
        ]);
    }

    public function test_admin_can_flush_dns_cache(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.flush-dns'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
