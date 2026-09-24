<?php

namespace Tests\Feature;

use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsRecordManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected DnsZone $zone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->zone = DnsZone::create([
            'domain' => 'mybrandtest.com',
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_dns_records_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.records', ['zone_id' => $this->zone->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/DNS/Records')
            ->has('zones')
            ->has('selectedZone')
            ->has('records')
            ->has('stats')
        );
    }

    public function test_admin_can_create_dns_record(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.records.store'), [
                'dns_zone_id' => $this->zone->id,
                'name' => 'api',
                'type' => 'A',
                'content' => '103.59.177.138',
                'ttl' => 3600,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => 'api',
            'type' => 'A',
            'content' => '103.59.177.138',
        ]);
    }

    public function test_admin_can_update_dns_record(): void
    {
        $record = $this->zone->records()->create([
            'name' => 'blog',
            'type' => 'CNAME',
            'content' => 'mybrandtest.com.',
            'ttl' => 3600,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.dns.records.update', $record->id), [
                'name' => 'blog',
                'type' => 'A',
                'content' => '103.59.177.138',
                'ttl' => 1800,
            ]);

        $response->assertRedirect();
        $this->assertEquals('A', $record->fresh()->type);
        $this->assertEquals('103.59.177.138', $record->fresh()->content);
    }

    public function test_admin_can_apply_1click_presets(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.records.apply-preset'), [
                'dns_zone_id' => $this->zone->id,
                'preset' => 'google_workspace',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'type' => 'MX',
            'content' => 'aspmx.l.google.com.',
        ]);
    }

    public function test_admin_can_delete_dns_record(): void
    {
        $record = $this->zone->records()->create([
            'name' => 'staging',
            'type' => 'A',
            'content' => '103.59.177.138',
            'ttl' => 3600,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.dns.records.destroy', $record->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('dns_records', [
            'id' => $record->id,
        ]);
    }
}
