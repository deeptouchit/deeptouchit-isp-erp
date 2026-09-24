<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocalizationSettingsTest extends TestCase
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

    public function test_admin_can_view_localization_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.localization'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/Localization/Index')
            ->has('settings')
            ->has('stats')
            ->has('timezones')
            ->has('locales')
            ->where('settings.currency_code', 'BDT')
            ->where('settings.timezone', 'Asia/Dhaka')
        );
    }

    public function test_admin_can_update_localization_settings(): void
    {
        $payload = [
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'decimal_separator' => '.',
            'thousand_separator' => ',',
            'decimal_precision' => 2,
            'timezone' => 'America/New_York',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'first_day_of_week' => 'monday',
            'default_locale' => 'en',
            'allow_client_language' => true,
            'rtl_support' => false,
            'exchange_rates' => [
                ['code' => 'EUR', 'symbol' => '€', 'rate' => 0.92, 'enabled' => true],
                ['code' => 'GBP', 'symbol' => '£', 'rate' => 0.79, 'enabled' => true],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.localization.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('USD', SystemSetting::get('localization.currency_code'));
        $this->assertEquals('$', SystemSetting::get('localization.currency_symbol'));
        $this->assertEquals('America/New_York', SystemSetting::get('localization.timezone'));
        $this->assertEquals('24h', SystemSetting::get('localization.time_format'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_localization_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_currency_or_timezone(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.localization.update'), [
                'currency_code' => 'INVALID_CODE',
                'timezone' => 'Invalid/Fake_Zone',
            ]);

        $response->assertSessionHasErrors(['currency_code', 'timezone']);
    }

    public function test_admin_can_reset_localization_settings_to_defaults(): void
    {
        // First set modified values
        SystemSetting::set('localization.currency_code', 'EUR', 'localization');
        SystemSetting::set('localization.timezone', 'Europe/Berlin', 'localization');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.localization.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('BDT', SystemSetting::get('localization.currency_code'));
        $this->assertEquals('Asia/Dhaka', SystemSetting::get('localization.timezone'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_localization_settings',
        ]);
    }
}
