<?php

namespace Tests\Feature;

use App\Models\EmailSpamSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSpamManagementTest extends TestCase
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

    public function test_admin_can_view_spam_protection_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.spam'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Spam')
            ->has('setting')
            ->has('stats')
            ->has('daemonStatus')
        );
    }

    public function test_admin_can_update_spam_protection_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.spam.update'), [
                'required_score' => 4.5,
                'rewrite_subject' => true,
                'subject_tag' => '***SUSPICIOUS***',
                'auto_delete_score' => 18.0,
                'is_auto_delete_enabled' => true,
                'whitelist' => '*@trusted.com',
                'blacklist' => '*@badguy.top',
                'bayesian_filter_enabled' => true,
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_spam_settings', [
            'required_score' => 4.5,
            'subject_tag' => '***SUSPICIOUS***',
            'is_auto_delete_enabled' => true,
        ]);
    }

    public function test_admin_can_restart_spam_daemon(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.spam.restart'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
