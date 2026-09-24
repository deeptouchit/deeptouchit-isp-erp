<?php

namespace Tests\Feature;

use App\Models\EmailRelaySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailRelayManagementTest extends TestCase
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

    public function test_admin_can_view_email_relay_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.relay'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Relay')
            ->has('setting')
            ->has('providers')
            ->has('stats')
        );
    }

    public function test_admin_can_update_email_relay_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.relay.update'), [
                'is_enabled' => true,
                'mode' => 'relay',
                'provider' => 'brevo',
                'host' => 'smtp-relay.brevo.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'apikey_user',
                'password' => 'xsmtpsib-secret',
                'sender_domain' => 'deeptouchit.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_relay_settings', [
            'is_enabled' => true,
            'provider' => 'brevo',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
        ]);
    }

    public function test_admin_can_execute_diagnostic_relay_probe(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.relay.test'), [
                'test_email' => 'salzarrahman84@gmail.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
