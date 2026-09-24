<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminBackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@deeptouchhost.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'client',
            'email' => 'client@deeptouchhost.com',
        ]);
    }

    /**
     * Test admin can view backup settings console via canonical and alias routes.
     */
    public function test_admin_can_view_backup_settings_page(): void
    {
        // 1. Canonical route: /admin/system-settings/backup
        $response = $this->actingAs($this->admin)->get(route('admin.settings.backup'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Backup/Index')
            ->has('settings')
            ->has('stats')
            ->where('stats.server_ip', '103.59.177.138')
            ->where('stats.server_hostname', 'deeptouchit.com')
        );

        // 2. Requested alias route: /admin/settings/backup
        $responseAlias = $this->actingAs($this->admin)->get(route('admin.system_settings.backup'));
        $responseAlias->assertOk();
        $responseAlias->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Backup/Index')
            ->has('settings')
            ->has('stats')
        );
    }

    /**
     * Test admin can update backup GFS policies, database dump hardening, and replication rules.
     */
    public function test_admin_can_update_backup_policies(): void
    {
        $payload = [
            'auto_backup_enabled' => true,
            'default_storage_driver' => 'r2',
            'compression_algorithm' => 'zstd',
            'compression_level' => 8,
            'execution_window_hour' => 3,
            'temp_directory' => '/var/tmp/backups',

            'daily_retention_days' => 14,
            'weekly_retention_weeks' => 8,
            'monthly_retention_months' => 6,
            'auto_purge_orphaned' => true,

            'db_single_transaction' => true,
            'db_include_routines_triggers' => true,
            'db_quick_mode' => true,
            'db_lock_tables' => false,
            'db_max_allowed_packet_mb' => 512,

            'encryption_enabled' => true,
            'encryption_cipher' => 'AES-256-CBC',
            'max_backup_size_gb' => 100,
            'disk_safety_threshold_percent' => 90,
            'max_parallel_workers' => 4,

            'offsite_replication_enabled' => true,
            'purge_local_after_cloud_upload' => true,
            'upload_chunk_size_mb' => 50,
            'network_bandwidth_limit_mbps' => 100,
            'retry_count_on_failure' => 5,

            'notify_on_completion' => true,
            'notify_on_failure' => true,
            'alert_email' => 'ops@deeptouchhost.com',
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/X00',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.backup.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check values persisted in database
        $this->assertEquals('r2', SystemSetting::get('backup.default_storage_driver'));
        $this->assertEquals('zstd', SystemSetting::get('backup.compression_algorithm'));
        $this->assertEquals('8', SystemSetting::get('backup.compression_level'));
        $this->assertEquals('14', SystemSetting::get('backup.daily_retention_days'));
        $this->assertEquals('8', SystemSetting::get('backup.weekly_retention_weeks'));
        $this->assertEquals('6', SystemSetting::get('backup.monthly_retention_months'));
        $this->assertEquals('512', SystemSetting::get('backup.db_max_allowed_packet_mb'));
        $this->assertEquals('ops@deeptouchhost.com', SystemSetting::get('backup.alert_email'));

        // Check ActivityLog recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'update_backup_settings',
        ]);
    }

    /**
     * Test validation prevents invalid backup configuration parameters.
     */
    public function test_admin_cannot_update_with_invalid_backup_parameters(): void
    {
        $payload = [
            'default_storage_driver' => 'invalid_storage_driver', // Not in allowed list
            'compression_algorithm' => 'invalid_algo', // Not in allowed list
            'compression_level' => 99, // Exceeds max 9
            'execution_window_hour' => 35, // Exceeds 23
            'daily_retention_days' => 0, // Below min 1
            'encryption_cipher' => 'DES', // Not in allowed list
            'alert_email' => 'not-an-email', // Invalid email
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.backup.update'), $payload);

        $response->assertSessionHasErrors([
            'default_storage_driver',
            'compression_algorithm',
            'compression_level',
            'execution_window_hour',
            'daily_retention_days',
            'encryption_cipher',
            'alert_email',
        ]);
    }

    /**
     * Test admin can trigger automated backup readiness probe.
     */
    public function test_admin_can_trigger_backup_readiness_probe(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.backup.probe'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'trigger_backup_health_probe',
        ]);
    }

    /**
     * Test admin can reset backup settings to factory defaults.
     */
    public function test_admin_can_reset_backup_settings_to_defaults(): void
    {
        // First mutate a setting
        SystemSetting::set('backup.default_storage_driver', 'sftp', 'backup');
        SystemSetting::set('backup.compression_algorithm', 'bzip2', 'backup');
        SystemSetting::set('backup.daily_retention_days', 30, 'backup');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.backup.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check reset to default values
        $this->assertEquals('local', SystemSetting::get('backup.default_storage_driver'));
        $this->assertEquals('gzip', SystemSetting::get('backup.compression_algorithm'));
        $this->assertEquals('7', SystemSetting::get('backup.daily_retention_days'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'reset_backup_settings',
        ]);
    }

    /**
     * Test non-admin cannot access or modify backup settings.
     */
    public function test_non_admin_cannot_access_or_modify_backup_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.settings.backup'));
        $response->assertRedirect(route('admin.login'));

        $responsePost = $this->actingAs($this->regularUser)->post(route('admin.settings.backup.update'), [
            'daily_retention_days' => 10,
        ]);
        $responsePost->assertRedirect(route('admin.login'));
    }
}
