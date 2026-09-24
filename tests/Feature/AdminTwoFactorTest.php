<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
            'two_factor_enforced' => true,
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'admin',
            'is_staff' => true,
            'status' => 'active',
            'two_factor_confirmed_at' => null,
            'two_factor_enforced' => false,
        ]);

        SystemSetting::set('two_factor.admin_enforcement_policy', 'enforced_all', 'security');
        SystemSetting::set('two_factor.grace_period_days', '3', 'security');
        SystemSetting::set('two_factor.allowed_methods', ['totp_authenticator', 'email_otp'], 'security');
        SystemSetting::set('two_factor.remember_device_days', '30', 'security');
        SystemSetting::set('two_factor.client_policy', 'optional', 'security');
    }

    public function test_admin_can_view_2fa_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.2fa'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/TwoFactor/Index')
            ->has('operators')
            ->has('stats')
            ->has('policy')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_operators_by_compliance(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.2fa', ['compliance' => 'confirmed']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/TwoFactor/Index')
            ->where('filters.compliance', 'confirmed')
        );
    }

    public function test_admin_can_search_operators(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.2fa', ['search' => $this->staffUser->email]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/TwoFactor/Index')
            ->where('filters.search', $this->staffUser->email)
        );
    }

    public function test_admin_can_update_2fa_policy(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.2fa.policy'), [
                'admin_enforcement_policy' => 'enforced_all',
                'grace_period_days' => 5,
                'allowed_methods' => ['totp_authenticator', 'email_otp', 'webauthn_hardware'],
                'remember_device_days' => 45,
                'client_policy' => 'optional',
            ]);

        $response->assertRedirect(route('admin.administration.2fa'));
        $response->assertSessionHas('success');

        $this->assertEquals('enforced_all', SystemSetting::get('two_factor.admin_enforcement_policy'));
        $this->assertEquals('5', SystemSetting::get('two_factor.grace_period_days'));
    }

    public function test_admin_can_toggle_operator_2fa_enforcement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.2fa.toggle-enforce', $this->staffUser->id));

        $response->assertRedirect(route('admin.administration.2fa'));
        $response->assertSessionHas('success');

        $this->assertTrue($this->staffUser->fresh()->two_factor_enforced);
    }

    public function test_admin_can_reset_operator_2fa_credentials(): void
    {
        $this->adminUser->update([
            'two_factor_secret' => 'SECRET123',
            'two_factor_recovery_codes' => 'RECOVERY123',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.2fa.reset', $this->adminUser->id));

        $response->assertRedirect(route('admin.administration.2fa'));
        $response->assertSessionHas('success');

        $user = $this->adminUser->fresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }
}
