<?php

namespace Tests\Feature;

use App\Models\EmailDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSecurityManagementTest extends TestCase
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
            'domain' => 'securitytest.com',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_dkim_spf_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.dkim-spf'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/DkimSpf')
            ->has('domains')
            ->has('stats')
        );
    }

    public function test_admin_can_generate_dkim_key(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.dkim-spf.dkim', $this->domain->id), [
                'selector' => 'mail',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->domain->refresh();
        $this->assertNotEmpty($this->domain->dkim_public_key);
        $this->assertNotEmpty($this->domain->dkim_private_key);
    }

    public function test_admin_can_update_spf_record(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.dkim-spf.spf', $this->domain->id), [
                'spf_record' => 'v=spf1 mx a ip4:192.168.1.1 -all',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_domains', [
            'id' => $this->domain->id,
            'spf_record' => 'v=spf1 mx a ip4:192.168.1.1 -all',
        ]);
    }

    public function test_admin_can_update_dmarc_record(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.dkim-spf.dmarc', $this->domain->id), [
                'dmarc_record' => 'v=DMARC1; p=reject; pct=100',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_domains', [
            'id' => $this->domain->id,
            'dmarc_record' => 'v=DMARC1; p=reject; pct=100',
        ]);
    }
}
