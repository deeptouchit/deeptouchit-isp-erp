<?php

namespace Tests\Feature;

use App\Models\DnsTemplate;
use App\Models\DnsZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsTemplateManagementTest extends TestCase
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

    public function test_admin_can_view_dns_templates_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.dns.templates'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/DNS/Templates')
            ->has('templates')
            ->has('zones')
            ->has('stats')
        );
    }

    public function test_admin_can_create_custom_template(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.templates.store'), [
                'name' => 'Custom Node.js Template',
                'description' => 'Node.js staging blueprint',
                'is_default' => false,
                'records' => [
                    ['name' => '@', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
                    ['name' => 'api', 'type' => 'A', 'content' => '%ip%', 'ttl' => 3600],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dns_templates', [
            'name' => 'Custom Node.js Template',
            'is_system' => false,
        ]);
    }

    public function test_admin_can_apply_template_to_zone(): void
    {
        $template = DnsTemplate::create([
            'name' => 'Test Cluster Blueprint',
            'slug' => 'test-cluster',
            'is_system' => false,
        ]);

        $template->records()->create([
            'name' => 'staging',
            'type' => 'A',
            'content' => '%ip%',
            'ttl' => 3600,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.templates.apply', $template->id), [
                'zone_ids' => [$this->zone->id],
                'overwrite' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $this->zone->id,
            'name' => 'staging',
            'type' => 'A',
            'content' => \App\Support\ServerHelper::getPublicIp(),
        ]);
    }

    public function test_admin_can_set_default_template(): void
    {
        $template = DnsTemplate::create([
            'name' => 'New Global Default',
            'slug' => 'new-global-default',
            'is_default' => false,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.dns.templates.set-default', $template->id));

        $response->assertRedirect();
        $this->assertTrue($template->fresh()->is_default);
    }
}
