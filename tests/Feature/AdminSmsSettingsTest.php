<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmsSettingsTest extends TestCase
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

    public function test_admin_can_view_sms_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.sms'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/Sms/Index')
            ->has('settings')
            ->has('stats')
            ->where('settings.sms_provider', 'twilio')
            ->where('settings.default_country_code', '+880')
        );
    }

    public function test_admin_can_update_sms_settings(): void
    {
        $payload = [
            'sms_enabled' => true,
            'sms_provider' => 'ssl_wireless',
            'api_key' => 'SSL-API-KEY-9988',
            'api_secret' => 'SSL-SECRET-12345',
            'api_url' => 'https://smsplus.sslwireless.com/api/v3/send-sms',
            'sms_sender_id' => 'DeepTouchHostBD',
            'http_method' => 'POST',
            'sms_timeout' => 20,
            'enable_2fa_sms' => true,
            'enable_server_alert_sms' => true,
            'enable_invoice_sms' => true,
            'enable_welcome_sms' => true,
            'admin_alert_phone' => '+8801700000000',
            'sms_queue_enabled' => true,
            'rate_limit_per_minute' => 60,
            'default_country_code' => '+880',
            'masking_enabled' => true,
            'log_sms' => true,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.sms.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('ssl_wireless', SystemSetting::get('sms.sms_provider'));
        $this->assertEquals('DeepTouchHostBD', SystemSetting::get('sms.sms_sender_id'));
        $this->assertTrue(SystemSetting::get('sms.sms_enabled'));
        $this->assertTrue(SystemSetting::get('sms.masking_enabled'));
        $this->assertEquals(60, SystemSetting::get('sms.rate_limit_per_minute'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_sms_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_provider_or_timeout(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.sms.update'), [
                'sms_provider' => 'invalid_unknown_provider',
                'sms_timeout' => 1, // min is 3
            ]);

        $response->assertSessionHasErrors(['sms_provider', 'sms_timeout']);
    }

    public function test_admin_can_send_test_sms(): void
    {
        SystemSetting::set('sms.sms_provider', 'log', 'sms');
        SystemSetting::set('sms.sms_sender_id', 'DeepTouchHost', 'sms');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.sms.test'), [
                'phone_number' => '+8801712345678',
                'test_message' => 'Diagnostic test probe for DeepTouchHost SMS engine.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'send_test_sms',
        ]);
    }

    public function test_admin_can_reset_sms_settings_to_defaults(): void
    {
        SystemSetting::set('sms.sms_provider', 'bulksmsbd', 'sms');
        SystemSetting::set('sms.sms_sender_id', 'CustomSender', 'sms');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.sms.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('twilio', SystemSetting::get('sms.sms_provider'));
        $this->assertEquals('DeepTouchHost', SystemSetting::get('sms.sms_sender_id'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_sms_settings',
        ]);
    }
}
