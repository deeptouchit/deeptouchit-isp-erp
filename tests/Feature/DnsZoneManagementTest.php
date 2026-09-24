<?php

namespace Tests\Feature;

use App\Models\DnsZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsZoneManagementTest extends TestCase
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

    public function test_admin_can_view_dns_zones_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.zones'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/DNS/Zones')
            ->has('zones')
            ->has('stats')
        );
    }

    public function test_admin_can_create_dns_zone_with_default_records(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.zones.store'), [
                'domain' => 'testcompany.com',
                'server_ip' => '103.59.177.142',
                'primary_ns' => 'ns1.deeptouchit.com',
                'secondary_ns' => 'ns2.deeptouchit.com',
                'admin_email' => 'hostmaster.deeptouchit.com',
                'auto_populate' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dns_zones', [
            'domain' => 'testcompany.com',
            'primary_ns' => 'ns1.deeptouchit.com',
            'status' => 'active',
        ]);

        $zone = DnsZone::where('domain', 'testcompany.com')->first();
        $this->assertGreaterThan(0, $zone->records()->count());
    }

    public function test_admin_can_toggle_zone_status_and_dnssec(): void
    {
        $zone = DnsZone::create([
            'domain' => 'mybrand.org',
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
            'dnssec_enabled' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.zones.toggle-status', $zone->id));

        $response->assertRedirect();
        $this->assertEquals('disabled', $zone->fresh()->status);

        $responseSec = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.zones.toggle-dnssec', $zone->id));

        $responseSec->assertRedirect();
        $this->assertTrue($zone->fresh()->dnssec_enabled);
    }

    public function test_admin_can_fetch_raw_zone_file_and_export(): void
    {
        $zone = DnsZone::create([
            'domain' => 'exporttest.xyz',
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
        ]);

        $responseRaw = $this->actingAs($this->adminUser)
            ->getJson(route('admin.dns.zones.raw', $zone->id));

        $responseRaw->assertStatus(200);
        $responseRaw->assertJsonStructure(['success', 'domain', 'serial', 'raw']);

        $responseExport = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.zones.export', $zone->id));

        $responseExport->assertStatus(200);
        $responseExport->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_admin_can_delete_dns_zone(): void
    {
        $zone = DnsZone::create([
            'domain' => 'zonetodelete.com',
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.dns.zones.destroy', $zone->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('dns_zones', [
            'id' => $zone->id,
        ]);
    }
}
