<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNetworkTest extends TestCase
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

    public function test_admin_can_view_network_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.network'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Network/Index')
            ->has('interfaces')
            ->has('routes')
            ->has('dns_servers')
            ->has('listening_ports')
            ->has('stats')
        );
    }

    public function test_admin_can_run_ping_diagnostic(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.network.ping'), [
                'target' => '8.8.8.8',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'target',
            'transmitted',
            'received',
            'loss_percent',
            'avg_latency_ms',
            'output',
        ]);
        $this->assertEquals('8.8.8.8', $response->json('target'));
    }

    public function test_admin_can_run_dns_lookup_diagnostic(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.network.dns-lookup'), [
                'domain' => 'deeptouchit.com',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'domain',
            'records',
            'output',
        ]);
        $this->assertEquals('deeptouchit.com', $response->json('domain'));
    }
}
