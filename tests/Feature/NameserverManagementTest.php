<?php

namespace Tests\Feature;

use App\Models\DnsZone;
use App\Models\Nameserver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NameserverManagementTest extends TestCase
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

    public function test_admin_can_view_nameservers_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.nameservers'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/DNS/Nameservers')
            ->has('nameservers')
            ->has('stats')
        );
    }

    public function test_admin_can_create_nameserver(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.nameservers.store'), [
                'hostname' => 'ns3.deeptouchit.com',
                'ip_address' => '103.59.177.138',
                'is_primary' => false,
                'is_default' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('nameservers', [
            'hostname' => 'ns3.deeptouchit.com',
            'ip_address' => '103.59.177.138',
        ]);
    }

    public function test_admin_can_probe_nameserver_health(): void
    {
        $ns = Nameserver::create([
            'hostname' => 'ns-probe.test.com',
            'ip_address' => '127.0.0.1',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.nameservers.test-probe', $ns->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_sync_nameservers_to_all_zones(): void
    {
        $zone = DnsZone::create([
            'domain' => 'synctestdomain.com',
            'primary_ns' => 'old1.ns.com',
            'secondary_ns' => 'old2.ns.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.nameservers.sync-zones'), [
                'primary_ns' => 'ns1.deeptouchit.com',
                'secondary_ns' => 'ns2.deeptouchit.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('ns1.deeptouchit.com', $zone->fresh()->primary_ns);
    }
}
