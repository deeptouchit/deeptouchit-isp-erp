<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminNotificationSettingsTest extends TestCase
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

    public function test_admin_can_view_notification_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.notifications'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/Notifications/Index')
            ->has('settings')
            ->has('eventMatrix')
            ->has('stats')
            ->where('stats.total_channels', 7)
            ->where('settings.channel_mail_enabled', true)
        );
    }

    public function test_admin_can_update_notification_settings_and_matrix(): void
    {
        $matrix = [
            'server_offline' => [
                'name' => 'Server Offline / Heartbeat Lost',
                'module' => 'Infrastructure',
                'severity' => 'emergency',
                'description' => 'Test description',
                'mail' => true,
                'sms' => true,
                'in_app' => true,
                'slack' => true,
                'discord' => true,
                'telegram' => true,
            ],
            'backup_failed' => [
                'name' => 'Automated Backup Failed',
                'module' => 'Backups',
                'severity' => 'critical',
                'description' => 'Backup test',
                'mail' => true,
                'sms' => false,
                'in_app' => true,
                'slack' => true,
                'discord' => false,
                'telegram' => false,
            ],
        ];

        $payload = [
            'channel_mail_enabled' => true,
            'channel_sms_enabled' => true,
            'channel_in_app_enabled' => true,
            'channel_slack_enabled' => true,
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00/B00/X00',
            'slack_channel' => '#ops-alerts',
            'channel_discord_enabled' => true,
            'discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc',
            'channel_telegram_enabled' => true,
            'telegram_bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'telegram_chat_id' => '-100987654321',
            'channel_webhook_enabled' => true,
            'webhook_url' => 'https://siem.example.com/alerts',
            'webhook_secret' => 'supersecrettoken',
            'admin_recipient_emails' => 'noc@deeptouchit.com, devops@deeptouchhost.com',
            'admin_recipient_phones' => '+8801711223344',
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '06:00',
            'min_severity_level' => 'warning',
            'rate_limit_per_minute' => 120,
            'event_matrix' => $matrix,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.notifications.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue(SystemSetting::get('notifications.channel_slack_enabled'));
        $this->assertEquals('https://hooks.slack.com/services/T00/B00/X00', SystemSetting::get('notifications.slack_webhook_url'));
        $this->assertEquals(120, SystemSetting::get('notifications.rate_limit_per_minute'));
        $this->assertTrue(SystemSetting::get('notifications.quiet_hours_enabled'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_notification_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_severity_or_rate_limit(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.notifications.update'), [
                'min_severity_level' => 'invalid_level',
                'rate_limit_per_minute' => 0, // min 1
            ]);

        $response->assertSessionHasErrors(['min_severity_level', 'rate_limit_per_minute']);
    }

    public function test_admin_can_send_in_app_notification_probe(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.notifications.probe'), [
                'channel' => 'in_app',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->adminUser->id,
            'type' => 'App\Notifications\SystemDiagnosticProbeNotification',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'send_notification_test_probe',
        ]);
    }

    public function test_admin_can_reset_notification_settings_to_defaults(): void
    {
        SystemSetting::set('notifications.channel_slack_enabled', true, 'notifications');
        SystemSetting::set('notifications.min_severity_level', 'emergency', 'notifications');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.notifications.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse(SystemSetting::get('notifications.channel_slack_enabled'));
        $this->assertEquals('warning', SystemSetting::get('notifications.min_severity_level'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_notification_settings',
        ]);
    }
}
