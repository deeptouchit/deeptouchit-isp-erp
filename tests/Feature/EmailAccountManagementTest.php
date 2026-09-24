<?php

namespace Tests\Feature;

use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAccountManagementTest extends TestCase
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
            'domain' => 'testapp.com',
            'status' => 'active',
            'max_accounts' => 20,
            'max_quota_mb' => 20480,
        ]);
    }

    public function test_admin_can_view_email_accounts_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.accounts'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Accounts')
            ->has('accounts')
            ->has('stats')
            ->has('domains')
        );
    }

    public function test_admin_can_create_email_account(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.accounts.store'), [
                'email_domain_id' => $this->domain->id,
                'username' => 'support',
                'password' => 'SecurePass123!@#',
                'quota_mb' => 2048,
                'forward_to' => 'boss@gmail.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_accounts', [
            'email' => 'support@testapp.com',
            'status' => 'active',
            'quota_mb' => 2048,
        ]);
    }

    public function test_admin_can_update_mailbox_quota(): void
    {
        $account = EmailAccount::create([
            'email_domain_id' => $this->domain->id,
            'email' => 'sales@testapp.com',
            'password' => 'secret123',
            'quota_mb' => 1024,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.accounts.quota', $account->id), [
                'quota_mb' => 4096,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_accounts', [
            'id' => $account->id,
            'quota_mb' => 4096,
        ]);
    }

    public function test_admin_can_delete_email_account(): void
    {
        $account = EmailAccount::create([
            'email_domain_id' => $this->domain->id,
            'email' => 'temp@testapp.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.email.accounts.destroy', $account->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_accounts', [
            'id' => $account->id,
        ]);
    }
}
