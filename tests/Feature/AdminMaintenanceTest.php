<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->server = Server::create([
            'name' => 'Master Node 01',
            'hostname' => 'master.deeptouchhost.local',
            'ip_address' => '103.59.177.138',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
            'is_master' => true,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('framework/down'));
        \Illuminate\Support\Facades\Artisan::call('up');
        parent::tearDown();
    }

    public function test_admin_can_view_maintenance_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.maintenance'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/Maintenance')
            ->has('stats')
            ->has('downData')
            ->has('servers')
            ->has('routines')
        );
    }

    public function test_admin_can_fetch_maintenance_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.maintenance'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'is_down',
                'status_label',
                'total_servers',
            ],
            'servers',
            'routines',
        ]);
    }

    public function test_admin_can_enable_and_disable_maintenance_mode(): void
    {
        // Enable
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.maintenance.enable'), [
                'secret' => 'test-secret-12345',
                'retry' => 300,
                'status' => 503,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Disable
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.maintenance.disable'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_run_maintenance_routine(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.maintenance.routine', ['routineId' => 'clear_all_caches']));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'routine_id',
            'duration_ms',
            'stdout',
        ]);
    }

    public function test_admin_can_toggle_server_maintenance_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.maintenance.server.toggle', ['id' => $this->server->id]));

        $response->assertRedirect();
        $this->assertEquals(\App\Enums\Infrastructure\ServerStatus::MAINTENANCE, $this->server->fresh()->status);
    }
}
