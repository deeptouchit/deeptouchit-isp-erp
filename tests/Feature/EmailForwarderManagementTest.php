<?php

namespace Tests\Feature;

use App\Models\EmailDomain;
use App\Models\EmailForwarder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailForwarderManagementTest extends TestCase
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
            'domain' => 'forwardtest.com',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_email_forwarders_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.forwarders'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Forwarders')
            ->has('forwarders')
            ->has('stats')
            ->has('domains')
        );
    }

    public function test_admin_can_create_email_forwarder(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.forwarders.store'), [
                'email_domain_id' => $this->domain->id,
                'source' => 'info',
                'destination' => 'admin@gmail.com, boss@company.com',
                'keep_local_copy' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('email_forwarders', [
            'source' => 'info@forwardtest.com',
            'keep_local_copy' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_email_forwarder(): void
    {
        $forwarder = EmailForwarder::create([
            'email_domain_id' => $this->domain->id,
            'source' => 'billing@forwardtest.com',
            'destination' => 'old@gmail.com',
            'keep_local_copy' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.email.forwarders.update', $forwarder->id), [
                'destination' => 'newtarget@gmail.com',
                'keep_local_copy' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_forwarders', [
            'id' => $forwarder->id,
            'destination' => 'newtarget@gmail.com',
            'keep_local_copy' => true,
        ]);
    }

    public function test_admin_can_delete_email_forwarder(): void
    {
        $forwarder = EmailForwarder::create([
            'email_domain_id' => $this->domain->id,
            'source' => 'temp@forwardtest.com',
            'destination' => 'target@gmail.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.email.forwarders.destroy', $forwarder->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('email_forwarders', [
            'id' => $forwarder->id,
        ]);
    }
}
