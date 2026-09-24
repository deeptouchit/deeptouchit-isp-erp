<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_server_groups_page(): void
    {
        $group = ServerGroup::factory()->create([
            'name' => 'Europe Web Cluster',
            'location' => 'Frankfurt',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.infrastructure.groups'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Infrastructure/Groups/Index')
            ->has('groups.data', 1)
            ->has('stats')
        );
    }

    public function test_admin_can_create_server_group(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.infrastructure.groups.store'), [
                'name' => 'US East Storage Cluster',
                'location' => 'New York, US',
                'description' => 'Dedicated backup & block storage pool',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('server_groups', [
            'name' => 'US East Storage Cluster',
            'slug' => 'us-east-storage-cluster',
            'location' => 'New York, US',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_server_group(): void
    {
        $group = ServerGroup::factory()->create([
            'name' => 'Old Name Cluster',
            'location' => 'London',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.infrastructure.groups.update', $group->id), [
                'name' => 'London Edge Cluster',
                'location' => 'London, UK',
                'description' => 'Updated high speed edge pool',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('server_groups', [
            'id' => $group->id,
            'name' => 'London Edge Cluster',
            'slug' => 'london-edge-cluster',
            'location' => 'London, UK',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_delete_server_group_safely_unassigning_servers(): void
    {
        $group = ServerGroup::factory()->create([
            'name' => 'Cluster To Delete',
        ]);

        $server = Server::factory()->create([
            'name' => 'Worker 01',
            'server_group_id' => $group->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.infrastructure.groups.destroy', $group->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('server_groups', ['id' => $group->id]);

        // Verify server was not deleted but safely unassigned
        $server->refresh();
        $this->assertNull($server->server_group_id);
    }
}
