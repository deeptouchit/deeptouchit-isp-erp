<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureHealthTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_infrastructure_health_matrix(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
            'health_status' => 'healthy',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.infrastructure.health'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Infrastructure/Health/Index')
            ->has('servers', 1)
            ->has('stats')
            ->has('subsystems', 8)
            ->has('healthEvents')
        );
    }

    public function test_admin_can_trigger_full_infrastructure_health_probe(): void
    {
        $server = Server::factory()->create([
            'name' => 'Worker Node 02',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.health.probe'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $server->refresh();
        $this->assertNotNull($server->last_health_check_at);
    }
}
