<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTerminalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
        ]);
    }

    public function test_admin_can_view_terminal_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.terminal'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/RootTools/Terminal/Index')
            ->has('stats')
            ->has('presets')
            ->has('auditLogs')
        );
    }

    public function test_non_admin_cannot_access_terminal(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get(route('admin.root-tools.terminal'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_execute_terminal_command(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.terminal.execute'), [
                'command' => 'echo "DeepTouchHost Terminal Online"',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'command' => 'echo "DeepTouchHost Terminal Online"',
            'exit_code' => 0,
        ]);
        $this->assertStringContainsString('DeepTouchHost Terminal Online', $response->json('output'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'terminal_command',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_navigate_directory(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.terminal.execute'), [
                'command' => 'cd /var/www',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'command' => 'cd /var/www',
            'exit_code' => 0,
            'cwd' => '/var/www',
        ]);
    }

    public function test_admin_can_navigate_with_smart_shortcut(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.terminal.execute'), [
                'command' => 'cd vhosts',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('exit_code'));
        $this->assertStringContainsString('vhosts', $response->json('cwd'));
    }

    public function test_admin_can_clear_terminal_buffer(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.terminal.execute'), [
                'command' => 'clear',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'is_clear' => true,
            'exit_code' => 0,
        ]);
    }

    public function test_dangerous_destructive_commands_are_blocked(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.root-tools.terminal.execute'), [
                'command' => 'rm -rf /',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'exit_code' => 126,
        ]);
        $this->assertStringContainsString('Root Guard', $response->json('output'));
    }

    public function test_admin_can_clear_terminal_audit_logs(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'terminal_command',
            'description' => 'Executed: ls -la',
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'terminal_command']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.terminal.clear-logs'));

        $response->assertRedirect();
        $this->assertDatabaseMissing('activity_logs', ['action' => 'terminal_command']);
    }
}
