<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrandingSettingsTest extends TestCase
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

    public function test_admin_can_view_branding_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.branding'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Settings/Branding/Index')
            ->has('settings')
            ->has('stats')
            ->where('settings.primary_color', '#673DE6')
        );
    }

    public function test_admin_can_update_branding_settings(): void
    {
        $payload = [
            'brand_name' => 'HostPro Cloud',
            'tagline' => 'Enterprise Server Fleet',
            'logo_light_url' => 'https://cdn.example.com/logo.svg',
            'logo_dark_url' => 'https://cdn.example.com/logo-dark.svg',
            'favicon_url' => '/favicon.ico',
            'primary_color' => '#2563EB',
            'secondary_color' => '#1D4ED8',
            'admin_theme' => 'dark',
            'border_style' => 'rounded-xl',
            'white_label_enabled' => true,
            'title_suffix' => '| HostPro Cloud Enterprise',
            'copyright_text' => '© 2026 HostPro Ltd.',
            'help_url' => 'https://help.example.com',
            'terms_url' => 'https://example.com/terms',
            'privacy_url' => 'https://example.com/privacy',
            'custom_css' => 'body { font-family: sans-serif; }',
            'custom_js' => 'console.log("ready");',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.branding.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('HostPro Cloud', SystemSetting::get('branding.brand_name'));
        $this->assertEquals('#2563EB', SystemSetting::get('branding.primary_color'));
        $this->assertTrue(SystemSetting::get('branding.white_label_enabled'));
        $this->assertEquals('rounded-xl', SystemSetting::get('branding.border_style'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'update_branding_settings',
        ]);
    }

    public function test_admin_cannot_update_with_invalid_color_hex(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.branding.update'), [
                'brand_name' => 'HostPro',
                'primary_color' => 'invalid-color',
                'copyright_text' => '© 2026',
            ]);

        $response->assertSessionHasErrors(['primary_color']);
    }

    public function test_admin_can_reset_branding_settings_to_defaults(): void
    {
        // First set a modified value
        SystemSetting::set('branding.brand_name', 'Custom Modified Brand', 'branding');
        SystemSetting::set('branding.primary_color', '#DC2626', 'branding');

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.branding.reset'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('DeepTouchHost', SystemSetting::get('branding.brand_name'));
        $this->assertEquals('#673DE6', SystemSetting::get('branding.primary_color'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'reset_branding_settings',
        ]);
    }
}
