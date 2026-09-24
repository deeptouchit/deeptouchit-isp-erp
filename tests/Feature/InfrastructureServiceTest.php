<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureServiceTest extends TestCase
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

    public function test_admin_can_view_infrastructure_services_matrix(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        ServerService::create([
            'server_id' => $server->id,
            'service_name' => 'nginx',
            'display_name' => 'Nginx Web Server',
            'service_type' => 'webserver',
            'status' => 'running',
            'version' => '1.24.0',
            'port' => 80,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.infrastructure.services'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Infrastructure/Services/Index')
            ->has('services', 1)
            ->has('stats')
            ->has('servers')
        );
    }

    public function test_admin_can_manage_infrastructure_service(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        $service = ServerService::create([
            'server_id' => $server->id,
            'service_name' => 'nginx',
            'display_name' => 'Nginx Web Server',
            'service_type' => 'webserver',
            'status' => 'running',
            'version' => '1.24.0',
            'port' => 80,
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.services.manage', $service->id), [
                'action' => 'restart',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_sync_all_infrastructure_services(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.services.sync-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
