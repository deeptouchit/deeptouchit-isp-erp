<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupLogsTest extends TestCase
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

    public function test_admin_can_view_backup_logs_page(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'backup_created',
            'description' => 'Generated backup archive test.tar.gz',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI',
            'old_values' => [],
            'new_values' => ['size' => 1024],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.logs'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Logs')
            ->has('logs')
            ->has('stats')
        );
    }

    public function test_admin_can_flush_backup_logs(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'backup_created',
            'description' => 'Generated backup archive test.tar.gz',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI',
            'old_values' => [],
            'new_values' => [],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.logs.flush'));

        $response->assertRedirect(route('admin.backups.logs'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'backup_created',
            'description' => 'Generated backup archive test.tar.gz',
        ]);
    }
}
