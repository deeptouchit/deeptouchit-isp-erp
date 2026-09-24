<?php

namespace Tests\Feature;

use App\Models\EmailAutoResponder;
use App\Models\EmailDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAutoResponderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected EmailDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->domain = EmailDomain::create([
            'domain' => 'responderapp.com',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_auto_responders_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.auto-responders'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/AutoResponders')
            ->has('autoResponders')
            ->has('stats')
            ->has('domains')
        );
    }

    public function test_admin_can_create_auto_responder(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.auto-responders.store'), [
                'email_domain_id' => $this->domain->id,
                'email_prefix' => 'vacation',
                'from_name' => 'John Doe',
                'subject' => 'I am on vacation',
                'body' => 'I will be back in office on Monday.',
                'interval_hours' => 24,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_auto_responders', [
            'email' => 'vacation@responderapp.com',
            'subject' => 'I am on vacation',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_toggle_auto_responder_status(): void
    {
        $responder = EmailAutoResponder::create([
            'email_domain_id' => $this->domain->id,
            'email' => 'toggle@responderapp.com',
            'subject' => 'Auto reply test',
            'body' => 'Thank you for reaching out.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.auto-responders.toggle', $responder->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('email_auto_responders', [
            'id' => $responder->id,
            'status' => 'paused',
        ]);
    }

    public function test_admin_can_delete_auto_responder(): void
    {
        $responder = EmailAutoResponder::create([
            'email_domain_id' => $this->domain->id,
            'email' => 'delete@responderapp.com',
            'subject' => 'To delete',
            'body' => 'Delete me',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.email.auto-responders.destroy', $responder->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_auto_responders', [
            'id' => $responder->id,
        ]);
    }
}
