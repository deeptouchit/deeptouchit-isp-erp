<?php

namespace Tests\Feature;

use App\Models\BackupJob;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Subscription $subscription;

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
            'domain' => 'backup-demo.com',
            'username' => 'backupuser',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 10,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_admin_can_view_backups_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Index')
            ->has('backups')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_view_backup_jobs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.jobs'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Jobs')
            ->has('jobs')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_create_backup_job(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.store'), [
                'subscription_id' => $this->subscription->id,
                'name' => 'Test Manual Backup',
                'type' => 'full',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_jobs', [
            'subscription_id' => $this->subscription->id,
            'name' => 'Test Manual Backup',
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_retry_backup_job(): void
    {
        $backup = BackupJob::create([
            'subscription_id' => $this->subscription->id,
            'name' => 'Failed Job to Retry',
            'type' => 'full',
            'status' => 'failed',
            'file_path' => '/var/backups/deeptouchhost/failed.tar.gz',
            'file_size' => 0,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.retry', $backup->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_delete_backup_archive(): void
    {
        $backup = BackupJob::create([
            'subscription_id' => $this->subscription->id,
            'name' => 'Backup to delete',
            'type' => 'full',
            'status' => 'completed',
            'file_path' => '/tmp/dummy_backup.tar.gz',
            'file_size' => 1024,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.backups.destroy', $backup->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('backup_jobs', ['id' => $backup->id]);
    }
}
