<?php

namespace Tests\Feature;

use App\Models\FirewallRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirewallManagementTest extends TestCase
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

    public function test_admin_can_view_firewall_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.firewall'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/Firewall')
            ->has('rules')
            ->has('listeningPorts')
            ->has('stats')
        );
    }

    public function test_admin_can_create_custom_firewall_rule(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.firewall.store'), [
                'label' => 'Test Custom Service',
                'port' => '9090',
                'protocol' => 'tcp',
                'action' => 'allow',
                'from_ip' => 'Anywhere',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('firewall_rules', [
            'port' => '9090',
            'protocol' => 'tcp',
            'action' => 'allow',
        ]);
    }

    public function test_admin_can_update_firewall_rule(): void
    {
        $rule = FirewallRule::create([
            'label' => 'Old Label',
            'port' => '9090',
            'protocol' => 'tcp',
            'action' => 'allow',
            'from_ip' => 'Anywhere',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.security.firewall.update', $rule->id), [
                'label' => 'Updated Custom Label',
                'port' => '9091',
                'protocol' => 'tcp',
                'action' => 'limit',
                'from_ip' => '103.59.177.0/24',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('firewall_rules', [
            'id' => $rule->id,
            'label' => 'Updated Custom Label',
            'port' => '9091',
            'action' => 'limit',
            'from_ip' => '103.59.177.0/24',
        ]);
    }

    public function test_admin_can_apply_firewall_preset(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.firewall.apply-preset'), [
                'preset' => 'web',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('firewall_rules', [
            'port' => '80',
            'protocol' => 'tcp',
        ]);
    }

    public function test_admin_can_delete_firewall_rule(): void
    {
        $rule = FirewallRule::create([
            'label' => 'Rule To Delete',
            'port' => '8888',
            'protocol' => 'tcp',
            'action' => 'allow',
            'from_ip' => 'Anywhere',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.security.firewall.destroy', $rule->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('firewall_rules', [
            'id' => $rule->id,
        ]);
    }
}
