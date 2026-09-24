<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGeneralSettingsTest extends TestCase
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

    public function test_admin_can_view_general_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.general'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/General/Index')
            ->has('settings')
            ->has('stats')
            ->where('settings.server_ip', '103.59.177.138')
        );
    }

    public function test_admin_can_update_general_settings(): void
    {
        $payload = [
            'app_name' => 'DeepTouch Host Cloud Enterprise',
            'company_name' => 'DeepTouch Global Tech Ltd.',
            'app_url' => 'https://deeptouchit.com',
            'admin_email' => 'admin@deeptouchit.com',
            'support_email' => 'support@deeptouchit.com',
            'server_hostname' => 'deeptouchit.com',
            'server_ip' => '103.59.177.138',
            'admin_port' => 443,
            'force_https' => true,
            'maintenance_mode' => true,
            'maintenance_message' => 'Upgrading cloud hypervisors.',
            'maintenance_ip_allowlist' => '103.59.177.138, 127.0.0.1',
            'allow_registration' => false,
            'require_email_verification' => true,
            'session_timeout_minutes' => 30,
            'max_concurrent_sessions' => 2,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.general.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('DeepTouch Host Cloud Enterprise', SystemSetting::get('general.app_name'));
        $this->assertTrue(SystemSetting::get('general.maintenance_mode'));
        $this->assertFalse(SystemSetting::get('general.allow_registration'));
        $this->assertEquals(30, SystemSetting::get('general.session_timeout_minutes'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_general_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.general.update'), [
                'app_name' => '',
                'app_url' => 'not-a-valid-url',
                'server_ip' => 'not-an-ip',
            ]);

        $response->assertSessionHasErrors(['app_name', 'app_url', 'server_ip']);
    }

    public function test_admin_can_reset_general_settings_to_defaults(): void
    {
        // First set a modified value
        SystemSetting::set('general.app_name', 'Custom Non-Default Name', 'general');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.general.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('DeepTouch Host Cloud Platform', SystemSetting::get('general.app_name'));
        $this->assertEquals('103.59.177.138', SystemSetting::get('general.server_ip'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_general_settings',
        ]);
    }
}
