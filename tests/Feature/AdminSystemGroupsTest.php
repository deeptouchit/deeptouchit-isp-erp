<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemGroupsTest extends TestCase
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

    public function test_admin_can_view_system_groups_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-groups'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemGroups/Index')
            ->has('groups')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_system_groups_by_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-groups', ['type' => 'privileged']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemGroups/Index')
            ->where('filters.type', 'privileged')
        );
    }

    public function test_admin_can_search_system_groups(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-groups', ['search' => 'sudo']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemGroups/Index')
            ->where('filters.search', 'sudo')
        );
    }

    public function test_admin_can_inspect_system_group_details(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.root-tools.system-groups.details', 'sudo'));

        $response->assertOk();
        $response->assertJsonStructure([
            'name',
            'gid',
            'type',
            'is_privileged',
            'supplementary_members',
            'primary_members',
            'total_associated_users',
        ]);
        $this->assertEquals('sudo', $response->json('name'));
    }
}
