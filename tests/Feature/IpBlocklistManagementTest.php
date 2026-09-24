<?php

namespace Tests\Feature;

use App\Models\IpBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpBlocklistManagementTest extends TestCase
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

    public function test_admin_can_view_blocklist_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.blocklist'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/Blocklist')
            ->has('ipBlocks')
            ->has('stats')
        );
    }

    public function test_admin_can_block_single_ip(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.blocklist.store'), [
                'ip_address' => '198.51.100.99',
                'reason' => 'Brute-force SSH attack',
                'duration' => '24h',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_blocks', [
            'ip_address' => '198.51.100.99',
            'reason' => 'Brute-force SSH attack',
            'is_subnet' => false,
        ]);
    }

    public function test_admin_can_block_cidr_subnet_and_bulk(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.blocklist.bulk'), [
                'ip_list' => "198.51.100.10\n198.51.100.20\n45.142.120.0/24",
                'reason' => 'Botnet Subnet',
                'duration' => 'permanent',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_blocks', [
            'ip_address' => '45.142.120.0/24',
            'is_subnet' => true,
        ]);
    }

    public function test_admin_can_update_and_unblock_ip(): void
    {
        $block = IpBlock::create([
            'ip_address' => '203.0.113.50',
            'reason' => 'Old reason',
            'type' => 'user',
            'created_by' => $this->adminUser->id,
        ]);

        $responseUpdate = $this->actingAs($this->adminUser)
            ->put(route('admin.security.blocklist.update', $block->id), [
                'reason' => 'Updated security reason',
                'duration' => '7d',
            ]);

        $responseUpdate->assertRedirect();
        $this->assertEquals('Updated security reason', $block->fresh()->reason);
        $this->assertNotNull($block->fresh()->expires_at);

        $responseDelete = $this->actingAs($this->adminUser)
            ->delete(route('admin.security.blocklist.destroy', $block->id));

        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('ip_blocks', ['id' => $block->id]);
    }

    public function test_admin_can_export_blocklist(): void
    {
        IpBlock::create([
            'ip_address' => '192.0.2.1',
            'reason' => 'Export test',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.blocklist.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('192.0.2.1', $response->getContent());
    }
}
