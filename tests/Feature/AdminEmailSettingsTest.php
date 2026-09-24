<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailSettingsTest extends TestCase
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

    public function test_admin_can_view_email_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.email'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/Email/Index')
            ->has('settings')
            ->has('stats')
            ->where('settings.mail_mailer', 'smtp')
            ->where('settings.mail_host', '127.0.0.1')
        );
    }

    public function test_admin_can_update_email_settings(): void
    {
        $payload = [
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.mailgun.org',
            'mail_port' => 587,
            'mail_username' => 'postmaster@mg.example.com',
            'mail_password' => 'secret-api-key-12345',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'dispatch@mg.example.com',
            'mail_from_name' => 'HostPro Cloud Dispatcher',
            'mail_reply_to' => 'support@example.com',
            'mail_timeout' => 45,
            'mail_queue_enabled' => true,
            'rate_limit_per_minute' => 120,
            'verify_ssl' => true,
            'log_emails' => true,
            'bcc_alerts' => 'alerts@example.com',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.email.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('smtp.mailgun.org', SystemSetting::get('email.mail_host'));
        $this->assertEquals('dispatch@mg.example.com', SystemSetting::get('email.mail_from_address'));
        $this->assertTrue(SystemSetting::get('email.mail_queue_enabled'));
        $this->assertEquals(120, SystemSetting::get('email.rate_limit_per_minute'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_email_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_mailer_or_port(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.email.update'), [
                'mail_mailer' => 'invalid_mailer_engine',
                'mail_port' => 999999, // exceeds max 65535
            ]);

        $response->assertSessionHasErrors(['mail_mailer', 'mail_port']);
    }

    public function test_admin_can_send_test_email(): void
    {
        Mail::fake();

        // Configure settings
        SystemSetting::set('email.mail_mailer', 'smtp', 'email');
        SystemSetting::set('email.mail_host', '127.0.0.1', 'email');
        SystemSetting::set('email.mail_from_address', 'test@deeptouchhost.local', 'email');
        SystemSetting::set('email.mail_from_name', 'DeepTouchHost Test', 'email');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.email.test'), [
                'recipient_email' => 'client@example.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'send_test_email',
        ]);
    }

    public function test_admin_can_reset_email_settings_to_defaults(): void
    {
        // First set modified values
        SystemSetting::set('email.mail_host', 'custom.smtp.example.com', 'email');
        SystemSetting::set('email.mail_port', 2525, 'email');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.email.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('127.0.0.1', SystemSetting::get('email.mail_host'));
        $this->assertEquals(587, SystemSetting::get('email.mail_port'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_email_settings',
        ]);
    }
}
