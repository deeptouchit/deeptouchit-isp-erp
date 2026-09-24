<?php

namespace Tests\Feature;

use App\Models\BackupJob;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminBackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Subscription $subscription;
    protected string $testBackupPath;

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
            'domain' => 'restore-test.com',
            'username' => 'restoreuser',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 10,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->testBackupPath = '/var/backups/deeptouchhost/test_archive.tar.gz';
        if (!File::exists('/var/backups/deeptouchhost')) {
            @mkdir('/var/backups/deeptouchhost', 0775, true);
        }
        @file_put_contents($this->testBackupPath, 'dummy_tar_content');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testBackupPath)) {
            @unlink($this->testBackupPath);
        }
        parent::tearDown();
    }

    public function test_admin_can_view_backup_restore_hub(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backups.restore'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Backups/Restore')
            ->has('snapshots')
            ->has('stats')
            ->has('recent_restores')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_restore_snapshot(): void
    {
        $job = BackupJob::create([
            'subscription_id' => $this->subscription->id,
            'name' => 'Test Restore Snapshot',
            'type' => 'full',
            'status' => 'completed',
            'file_path' => $this->testBackupPath,
            'file_size' => 1024,
            'storage_driver' => 'local',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.restore-action', $job->id), [
                'create_safety_snapshot' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_upload_and_restore_archive(): void
    {
        $file = UploadedFile::fake()->create('external_backup.tar.gz', 100, 'application/gzip');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backups.restore.upload'), [
                'archive_file' => $file,
                'subscription_id' => $this->subscription->id,
                'type' => 'full',
                'create_safety_snapshot' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
