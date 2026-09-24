<?php

namespace Tests\Feature;

use App\Models\EmailDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailDomainManagementTest extends TestCase
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

    public function test_admin_can_view_email_domains_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.domains'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Domains')
            ->has('domains')
            ->has('stats')
        );
    }

    public function test_admin_can_create_email_domain(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.domains.store'), [
                'domain' => 'testenterprise.com',
                'max_accounts' => 25,
                'max_quota_mb' => 5120,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_domains', [
            'domain' => 'testenterprise.com',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_toggle_domain_status(): void
    {
        $domain = EmailDomain::create([
            'domain' => 'toggle-test.com',
            'status' => 'active',
            'max_accounts' => 10,
            'max_quota_mb' => 1024,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.domains.toggle', $domain->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('email_domains', [
            'id' => $domain->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_delete_email_domain(): void
    {
        $domain = EmailDomain::create([
            'domain' => 'delete-test.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.email.domains.destroy', $domain->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_domains', [
            'id' => $domain->id,
        ]);
    }
}
