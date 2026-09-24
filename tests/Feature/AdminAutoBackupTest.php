<?php

namespace Tests\Feature;

use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutoBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected BackupStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->storage = BackupStorage::create([
            'name' => 'Local Disk Vault',
            'driver' => 'local',
            'config' => ['path' => '/var/backups'],
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_auto_backup_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.auto-backup'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/AutoBackup')
            ->has('schedules')
            ->has('stats')
            ->has('storages')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_fetch_auto_backup_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.auto-backup'));

        $response->assertOk();
        $response->assertJsonStructure([
            'schedules',
            'stats' => [
                'total_schedules',
                'active_schedules',
            ],
            'storages',
            'subscriptions',
        ]);
    }

    public function test_admin_can_create_and_update_backup_schedule(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-backup.store'), [
                'name' => 'Nightly Full System Snapshot',
                'backup_storage_id' => $this->storage->id,
                'frequency' => 'daily',
                'cron_expression' => '0 2 * * *',
                'type' => 'full',
                'retention_count' => 14,
                'status' => 'active',
                'notify_on_failure' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_schedules', [
            'name' => 'Nightly Full System Snapshot',
            'retention_count' => 14,
        ]);

        $schedule = BackupSchedule::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.automation.auto-backup.update', ['backupSchedule' => $schedule->id]), [
                'name' => 'Updated Nightly Full Snapshot',
                'backup_storage_id' => $this->storage->id,
                'frequency' => 'weekly',
                'cron_expression' => '0 3 * * 0',
                'type' => 'full',
                'retention_count' => 30,
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Updated Nightly Full Snapshot',
            'retention_count' => 30,
        ]);
    }

    public function test_admin_can_toggle_and_run_schedule_now(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Hourly DB Backup',
            'backup_storage_id' => $this->storage->id,
            'frequency' => 'hourly',
            'cron_expression' => '0 * * * *',
            'type' => 'database',
            'retention_count' => 24,
            'status' => 'active',
        ]);

        // Toggle
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-backup.toggle', ['backupSchedule' => $schedule->id]));

        $response->assertRedirect();
        $this->assertEquals('paused', $schedule->fresh()->status);

        // Run now
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-backup.run-now', ['backupSchedule' => $schedule->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_delete_backup_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Temporary Schedule',
            'backup_storage_id' => $this->storage->id,
            'frequency' => 'daily',
            'type' => 'full',
            'retention_count' => 7,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.automation.auto-backup.destroy', ['backupSchedule' => $schedule->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('backup_schedules', ['id' => $schedule->id]);
    }
}
