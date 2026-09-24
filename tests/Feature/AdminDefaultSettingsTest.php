<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminDefaultSettingsTest extends TestCase
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
     * Test admin can view system defaults console via canonical and alias routes.
     */
    public function test_admin_can_view_default_settings_page(): void
    {
        // 1. Canonical route: /admin/system-settings/defaults
        $response = $this->actingAs($this->admin)->get(route('admin.settings.defaults'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Defaults/Index')
            ->has('settings')
            ->has('stats')
            ->where('stats.server_ip', '103.59.177.138')
            ->where('stats.server_hostname', 'deeptouchit.com')
        );

        // 2. Requested alias route: /admin/settings/defaults
        $responseAlias = $this->actingAs($this->admin)->get(route('admin.system_settings.defaults'));
        $responseAlias->assertOk();
        $responseAlias->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Defaults/Index')
            ->has('settings')
            ->has('stats')
        );
    }

    /**
     * Test admin can update system hosting defaults and provisioning policies.
     */
    public function test_admin_can_update_system_defaults(): void
    {
        $payload = [
            'default_plan_id' => 1,
            'default_php_version' => '8.2',
            'default_web_server' => 'nginx',
            'default_document_root_format' => '/var/www/vhosts/{username}/{domain}/public_html',
            'default_subdomain_docroot_format' => '{domain_root}/subdomains/{subdomain}',
            'auto_ssl_on_provision' => true,
            'force_https_default' => true,

            'default_disk_quota_mb' => 10240, // 10 GB
            'default_bandwidth_monthly_gb' => 100,
            'default_inode_limit' => 250000,
            'default_max_databases' => 10,
            'default_max_email_accounts' => 25,
            'default_max_ftp_accounts' => 5,
            'default_max_subdomains' => 20,

            'default_shell_access' => 'jailed',
            'default_ssh_port' => 2222,
            'default_auto_backup' => true,
            'default_modsecurity_enabled' => true,
            'default_cagefs_enabled' => true,

            'primary_nameserver' => 'ns1.deeptouchhost.com',
            'secondary_nameserver' => 'ns2.deeptouchhost.com',
            'default_soa_email' => 'hostmaster.deeptouchhost.com',
            'default_ttl' => 7200,
            'auto_dns_zone_creation' => true,

            'php_memory_limit' => '512M',
            'php_max_execution_time' => 120,
            'php_upload_max_filesize' => '128M',
            'php_post_max_size' => '128M',
            'php_display_errors' => false,

            'suspended_page_message' => 'Custom account suspension notice.',
            'parking_page_enabled' => true,
            'parking_page_title' => 'Under Construction - DeepTouch Host Cloud',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.defaults.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check values in database
        $this->assertEquals('8.2', SystemSetting::get('defaults.default_php_version'));
        $this->assertEquals('10240', SystemSetting::get('defaults.default_disk_quota_mb'));
        $this->assertEquals('2222', SystemSetting::get('defaults.default_ssh_port'));
        $this->assertEquals('ns1.deeptouchhost.com', SystemSetting::get('defaults.primary_nameserver'));
        $this->assertEquals('512M', SystemSetting::get('defaults.php_memory_limit'));

        // Check ActivityLog recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'update_system_defaults',
        ]);
    }

    /**
     * Test validation fails for invalid provisioning default values.
     */
    public function test_admin_cannot_update_with_invalid_default_parameters(): void
    {
        $payload = [
            'default_php_version' => '5.6', // Not in allowed list
            'default_web_server' => 'caddy', // Not in allowed list
            'default_disk_quota_mb' => 10, // Below min 100
            'default_shell_access' => 'superuser', // Not in allowed list
            'default_ssh_port' => 70000, // Above max 65535
            'default_ttl' => 10, // Below min 60
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.defaults.update'), $payload);

        $response->assertSessionHasErrors([
            'default_php_version',
            'default_web_server',
            'default_disk_quota_mb',
            'default_shell_access',
            'default_ssh_port',
            'default_ttl',
        ]);
    }

    /**
     * Test admin can trigger dry-run provisioning simulation probe.
     */
    public function test_admin_can_trigger_provisioning_probe(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.defaults.probe'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'trigger_provisioning_probe',
        ]);
    }

    /**
     * Test admin can reset defaults to factory recommended values.
     */
    public function test_admin_can_reset_defaults_to_factory_settings(): void
    {
        // Mutate some settings
        SystemSetting::set('defaults.default_php_version', '7.4', 'defaults');
        SystemSetting::set('defaults.default_disk_quota_mb', 2048, 'defaults');
        SystemSetting::set('defaults.primary_nameserver', 'ns1.custom.com', 'defaults');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.defaults.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check reset to factory defaults
        $this->assertEquals('8.3', SystemSetting::get('defaults.default_php_version'));
        $this->assertEquals('5120', SystemSetting::get('defaults.default_disk_quota_mb'));
        $this->assertEquals('ns1.deeptouchit.com', SystemSetting::get('defaults.primary_nameserver'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'reset_system_defaults',
        ]);
    }

    /**
     * Test non-admin cannot access or modify system defaults.
     */
    public function test_non_admin_cannot_access_or_modify_default_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.settings.defaults'));
        $response->assertRedirect(route('admin.login'));

        $responsePost = $this->actingAs($this->regularUser)->post(route('admin.settings.defaults.update'), [
            'default_disk_quota_mb' => 2048,
        ]);
        $responsePost->assertRedirect(route('admin.login'));
    }
}
