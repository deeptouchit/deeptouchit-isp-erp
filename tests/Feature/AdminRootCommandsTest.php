<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRootCommandsTest extends TestCase
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

    public function test_admin_can_view_commands_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.commands'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Commands/Index')
            ->has('stats')
            ->has('presets')
            ->has('history')
        );
    }

    public function test_admin_can_execute_safe_command(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.commands.execute'), [
                'command' => 'uptime -p',
                'cwd' => '/var/www/deeptouchhost',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'command',
            'cwd',
            'exit_code',
            'duration_ms',
            'output',
        ]);
        $this->assertEquals('uptime -p', $response->json('command'));
        $this->assertEquals(0, $response->json('exit_code'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'run_root_command',
        ]);
    }

    public function test_admin_cannot_execute_dangerous_blocked_command(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.commands.execute'), [
                'command' => 'rm -rf /',
                'cwd' => '/var/www/deeptouchhost',
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'exit_code' => 126,
        ]);
    }
}
