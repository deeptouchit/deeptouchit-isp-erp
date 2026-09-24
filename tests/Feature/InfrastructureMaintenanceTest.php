<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfrastructureMaintenanceTest extends TestCase
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

    public function test_admin_can_view_infrastructure_maintenance_center(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        \App\Models\ServerEvent::create([
            'server_id' => $server->id,
            'event_type' => \App\Enums\Infrastructure\ServerEventType::SERVER_MAINTENANCE_STARTED,
            'severity' => \App\Enums\Infrastructure\ServerEventSeverity::INFO,
            'message' => 'Node entered maintenance mode',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.infrastructure.maintenance'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Infrastructure/Maintenance/Index')
            ->has('servers', 1)
            ->has('stats')
            ->has('routines')
            ->has('auditLogs', 1)
        );
    }

    public function test_admin_can_toggle_server_maintenance_mode(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.maintenance.node.toggle', $server->id), [
                'enabled' => true,
                'reason' => 'Scheduled kernel patching',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('maintenance', $server->fresh()->status->value ?? $server->fresh()->status);
        $this->assertEquals('Scheduled kernel patching', $server->fresh()->maintenance_reason);
    }

    public function test_admin_can_execute_maintenance_routine(): void
    {
        $server = Server::factory()->create([
            'name' => 'Master Node 01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.maintenance.routines.run'), [
                'routine' => 'php_opcache_flush',
                'server_id' => $server->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
