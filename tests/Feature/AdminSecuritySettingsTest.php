<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@deeptouchit.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'client',
            'email' => 'client@deeptouchit.com',
        ]);
    }

    /**
     * Test admin can view security settings via both canonical and system-settings routes.
     */
    public function test_admin_can_view_security_settings_page_via_both_routes(): void
    {
        // 1. Canonical route: /admin/settings/security
        $response = $this->actingAs($this->admin)->get(route('admin.settings.security'));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Security/Index')
            ->has('settings')
            ->has('stats')
            ->where('stats.server_ip', '103.59.177.138')
        );

        // 2. Requested route: /admin/system-settings/security
        $responseAlias = $this->actingAs($this->admin)->get(route('admin.system_settings.security'));
        $responseAlias->assertOk();
        $responseAlias->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Security/Index')
            ->has('settings')
            ->has('stats')
        );
    }

    /**
     * Test admin can update security policies.
     */
    public function test_admin_can_update_security_policies(): void
    {
        $payload = [
            'password_min_length' => 14,
            'password_require_uppercase' => true,
            'password_require_number' => true,
            'password_require_special' => true,
            'password_expiry_days' => 60,
            'password_history_limit' => 8,
            'force_change_first_login' => true,

            'session_lifetime_minutes' => 60,
            'session_idle_timeout_minutes' => 15,
            'single_active_session' => true,
            'remember_me_enabled' => false,
            'remember_me_days' => 14,

            'max_login_attempts' => 3,
            'lockout_duration_minutes' => 30,
            'auto_block_ip_after_lockouts' => true,
            'auto_block_threshold' => 2,
            'admin_rate_limit_per_minute' => 10,

            'force_ssl_admin' => true,
            'hsts_enabled' => true,
            'hsts_max_age' => 63072000,
            'hsts_include_subdomains' => true,
            'hsts_preload' => true,
            'x_frame_options' => 'DENY',
            'x_content_type_options' => true,
            'referrer_policy' => 'no-referrer',
            'csp_mode' => 'enforce',

            'ip_allowlist_enforced' => true,
            'admin_ip_allowlist' => '103.59.177.138, 192.168.1.0/24',
            'block_tor_nodes' => true,
            'block_public_proxies' => true,

            'detailed_audit_logging' => true,
            'log_failed_logins' => true,
            'log_admin_actions' => true,
            'audit_retention_days' => 365,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.security.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check values persisted in database
        $this->assertEquals('14', SystemSetting::get('security.password_min_length'));
        $this->assertEquals('true', SystemSetting::get('security.password_require_special'));
        $this->assertEquals('DENY', SystemSetting::get('security.x_frame_options'));
        $this->assertEquals('no-referrer', SystemSetting::get('security.referrer_policy'));
        $this->assertEquals('enforce', SystemSetting::get('security.csp_mode'));
        $this->assertEquals('365', SystemSetting::get('security.audit_retention_days'));

        // Check ActivityLog recorded
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'update_security_settings',
        ]);
    }

    /**
     * Test validation prevents invalid security parameters.
     */
    public function test_admin_cannot_update_with_invalid_security_parameters(): void
    {
        $payload = [
            'password_min_length' => 4, // Below minimum 8
            'password_expiry_days' => 9999, // Exceeds max 365
            'x_frame_options' => 'INVALID_FRAME_OPTION', // Not in allowed list
            'csp_mode' => 'invalid_mode', // Not in allowed list
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.settings.security.update'), $payload);

        $response->assertSessionHasErrors([
            'password_min_length',
            'password_expiry_days',
            'x_frame_options',
            'csp_mode',
        ]);
    }

    /**
     * Test admin can run security audit probe.
     */
    public function test_admin_can_run_security_audit_probe(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.security.audit'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'run_security_audit_probe',
        ]);
    }

    /**
     * Test admin can reset security settings to defaults.
     */
    public function test_admin_can_reset_security_settings_to_defaults(): void
    {
        // First mutate a setting
        SystemSetting::set('security.password_min_length', '20', 'security');
        SystemSetting::set('security.x_frame_options', 'DENY', 'security');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.security.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check reset to default values
        $this->assertEquals('10', SystemSetting::get('security.password_min_length'));
        $this->assertEquals('SAMEORIGIN', SystemSetting::get('security.x_frame_options'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'reset_security_settings',
        ]);
    }

    /**
     * Test non-admin cannot access security settings.
     */
    public function test_non_admin_cannot_access_or_modify_security_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.settings.security'));
        $response->assertRedirect(route('admin.login'));

        $responsePost = $this->actingAs($this->regularUser)->post(route('admin.settings.security.update'), [
            'password_min_length' => 12,
        ]);
        $responsePost->assertRedirect(route('admin.login'));
    }
}
