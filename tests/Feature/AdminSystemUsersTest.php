<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemUsersTest extends TestCase
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

    public function test_admin_can_view_system_users_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-users'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemUsers/Index')
            ->has('users')
            ->has('groups')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_system_users_by_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-users', ['type' => 'interactive']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemUsers/Index')
            ->where('filters.type', 'interactive')
        );
    }

    public function test_admin_can_search_system_users(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.system-users', ['search' => 'root']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/SystemUsers/Index')
            ->where('filters.search', 'root')
        );
    }

    public function test_admin_can_inspect_system_user_details(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.root-tools.system-users.details', 'root'));

        $response->assertOk();
        $response->assertJsonStructure([
            'username',
            'uid',
            'gid',
            'primary_group',
            'groups',
            'home',
            'shell',
        ]);
        $this->assertEquals('root', $response->json('username'));
    }

    public function test_admin_can_toggle_system_user_shell(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.system-users.toggle-shell'), [
                'username' => 'deeptouch',
                'shell' => '/bin/bash',
            ]);

        $response->assertRedirect(route('admin.root-tools.system-users'));
        $response->assertSessionHas('success');
    }

    public function test_admin_cannot_disable_root_shell(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.system-users.toggle-shell'), [
                'username' => 'root',
                'shell' => '/usr/sbin/nologin',
            ]);

        $response->assertRedirect(route('admin.root-tools.system-users'));
        $response->assertSessionHas('error');
    }
}
