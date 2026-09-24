<?php

namespace Tests\Feature;

use App\Models\IpAllowlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAllowlistManagementTest extends TestCase
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

    public function test_admin_can_view_allowlist_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.allowlist'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/Allowlist')
            ->has('ipAllowlists')
            ->has('stats')
            ->has('currentIp')
        );
    }

    public function test_admin_can_allow_single_ip(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.allowlist.store'), [
                'ip_address' => '103.59.177.50',
                'label' => 'Office Fiber Line',
                'scope' => 'global',
                'duration' => '30d',
                'notes' => 'Head office connection',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_allowlists', [
            'ip_address' => '103.59.177.50',
            'label' => 'Office Fiber Line',
            'scope' => 'global',
        ]);
    }

    public function test_admin_can_allow_my_current_ip(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.allowlist.my-ip'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_allowlists', [
            'label' => 'Lead Administrator Station (Auto-Detected)',
            'scope' => 'global',
        ]);
    }

    public function test_admin_can_update_and_remove_allowlist(): void
    {
        $allow = IpAllowlist::create([
            'ip_address' => '10.0.0.99',
            'label' => 'Old Label',
            'scope' => 'ssh_only',
            'created_by' => $this->adminUser->id,
        ]);

        $responseUpdate = $this->actingAs($this->adminUser)
            ->put(route('admin.security.allowlist.update', $allow->id), [
                'label' => 'Updated Office Bastion',
                'scope' => 'global',
                'duration' => '7d',
            ]);

        $responseUpdate->assertRedirect();
        $this->assertEquals('Updated Office Bastion', $allow->fresh()->label);
        $this->assertEquals('global', $allow->fresh()->scope);
        $this->assertNotNull($allow->fresh()->expires_at);

        $responseDelete = $this->actingAs($this->adminUser)
            ->delete(route('admin.security.allowlist.destroy', $allow->id));

        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('ip_allowlists', ['id' => $allow->id]);
    }

    public function test_admin_can_export_allowlist(): void
    {
        IpAllowlist::create([
            'ip_address' => '172.16.0.10',
            'label' => 'Export test',
            'scope' => 'global',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.allowlist.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('172.16.0.10', $response->getContent());
    }
}
