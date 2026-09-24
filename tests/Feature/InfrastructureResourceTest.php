<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureResourceTest extends TestCase
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

    public function test_admin_can_view_infrastructure_resources_matrix(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
            'cpu_cores' => 4,
            'total_ram' => 7285,
            'used_ram' => 3060,
            'total_disk' => 116500,
            'used_disk' => 9800,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.infrastructure.resources'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Infrastructure/Resources/Index')
            ->has('servers', 1)
            ->has('summary')
            ->has('workloadBreakdown')
            ->has('policies')
        );
    }

    public function test_admin_can_recalculate_infrastructure_resources(): void
    {
        $server = Server::factory()->create([
            'name' => 'Worker Node 02',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.resources.recalculate'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
