<?php

namespace Tests\Feature;

use App\Models\BackupSchedule;
use App\Models\BackupStorage;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Subscription $subscription;
    protected BackupStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $server = Server::factory()->create([
            'name' => 'Primary Node',
            'status' => 'online',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'disk_space' => 10000,
            'bandwidth' => 100000,
            'max_websites' => 5,
            'max_databases' => 5,
            'max_ftp_accounts' => 5,
            'max_email_accounts' => 5,
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->adminUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'schedule-demo.com',
            'username' => 'scheduleuser',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 10,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->storage = BackupStorage::create([
            'name' => 'Primary Node Vault',
            'driver' => 'local',
            'path' => '/var/backups/deeptouchhost',
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_backup_schedules_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.schedules'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Schedules')
            ->has('schedules')
            ->has('stats')
            ->has('storages')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_create_backup_schedule(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.schedules.store'), [
                'name' => 'Daily Off-Site Nightly',
                'subscription_id' => $this->subscription->id,
                'backup_storage_id' => $this->storage->id,
                'frequency' => 'daily',
                'cron_expression' => '0 2 * * *',
                'type' => 'full',
                'retention_count' => 14,
                'status' => 'active',
                'notify_on_failure' => true,
            ]);

        $response->assertRedirect(route('admin.backups.schedules'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_schedules', [
            'name' => 'Daily Off-Site Nightly',
            'frequency' => 'daily',
            'retention_count' => 14,
        ]);
    }

    public function test_admin_can_update_backup_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Old Schedule',
            'frequency' => 'daily',
            'cron_expression' => '0 2 * * *',
            'type' => 'full',
            'retention_count' => 7,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.backups.schedules.update', $schedule->id), [
                'name' => 'Updated Weekly Schedule',
                'frequency' => 'weekly',
                'cron_expression' => '0 4 * * 0',
                'type' => 'database',
                'retention_count' => 8,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.backups.schedules'));
        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'Updated Weekly Schedule',
            'frequency' => 'weekly',
        ]);
    }

    public function test_admin_can_toggle_backup_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Toggle Schedule',
            'frequency' => 'daily',
            'cron_expression' => '0 2 * * *',
            'type' => 'full',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.schedules.toggle', $schedule->id));

        $response->assertRedirect();
        $this->assertEquals('paused', $schedule->fresh()->status);
    }

    public function test_admin_can_run_schedule_now(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'Immediate Schedule',
            'subscription_id' => $this->subscription->id,
            'frequency' => 'daily',
            'cron_expression' => '0 2 * * *',
            'type' => 'full',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.schedules.run-now', $schedule->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_delete_backup_schedule(): void
    {
        $schedule = BackupSchedule::create([
            'name' => 'To Delete Schedule',
            'frequency' => 'daily',
            'cron_expression' => '0 2 * * *',
            'type' => 'full',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.backups.schedules.destroy', $schedule->id));

        $response->assertRedirect(route('admin.backups.schedules'));
        $this->assertDatabaseMissing('backup_schedules', ['id' => $schedule->id]);
    }
}
