<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventsManagementTest extends TestCase
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

    public function test_admin_can_view_security_events_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.events'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/Events')
            ->has('auditLogs')
            ->has('systemEvents')
            ->has('metrics')
        );
    }

    public function test_admin_can_purge_old_audit_logs(): void
    {
        \Illuminate\Support\Facades\DB::table('activity_logs')->insert([
            'user_id' => $this->adminUser->id,
            'action' => 'old_event',
            'description' => 'Old event to purge',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.events.purge'), [
                'days' => 30,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'old_event',
        ]);
    }

    public function test_admin_can_quick_block_ip_from_stream(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.events.quick-block'), [
                'ip' => '198.51.100.55',
                'reason' => 'Blocked from stream test',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_blocks', [
            'ip_address' => '198.51.100.55',
        ]);
    }

    public function test_admin_can_export_audit_trail(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'test_export_action',
            'description' => 'Test export description',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.events.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('test_export_action', $response->getContent());
    }
}
