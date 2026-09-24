<?php

namespace Tests\Feature;

use App\Models\DnsZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsDiagnosticsManagementTest extends TestCase
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

        DnsZone::create([
            'domain' => 'deeptouchit.com',
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
            'admin_email' => 'hostmaster.deeptouchit.com',
            'serial' => '2026083101',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_dns_diagnostics_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.diagnostics', ['domain' => 'deeptouchit.com']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/DNS/Diagnostics')
            ->has('audit')
            ->has('zones')
            ->has('rdns')
            ->has('stats')
        );
    }

    public function test_admin_can_execute_web_dig_query(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dns.diagnostics.dig'), [
                'domain' => 'deeptouchit.com',
                'type' => 'A',
                'nameserver' => '127.0.0.1',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'command', 'output', 'latency_ms']);
    }

    public function test_admin_can_check_rdns(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.dns.diagnostics.rdns'), [
                'ip' => '103.59.177.138',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['ip', 'hostname', 'has_rdns']);
    }
}
