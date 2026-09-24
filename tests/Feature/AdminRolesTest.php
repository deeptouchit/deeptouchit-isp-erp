<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRolesTest extends TestCase
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

        Permission::firstOrCreate(['name' => 'servers.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'servers.manage', 'guard_name' => 'web']);

        $role = Role::firstOrCreate(
            ['name' => 'test_role', 'guard_name' => 'web'],
            [
                'display_name' => 'Test Lead Role',
                'description' => 'Role for testing.',
                'is_system' => false,
                'color' => 'blue',
            ]
        );
        $role->syncPermissions(['servers.view']);
    }

    public function test_admin_can_view_roles_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.roles'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Roles/Index')
            ->has('roles')
            ->has('stats')
            ->has('allPermissions')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_roles_by_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.roles', ['type' => 'custom']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Roles/Index')
            ->where('filters.type', 'custom')
        );
    }

    public function test_admin_can_search_roles(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.roles', ['search' => 'Test Lead Role']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Roles/Index')
            ->where('filters.search', 'Test Lead Role')
        );
    }

    public function test_admin_can_create_custom_role_with_permissions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.roles.store'), [
                'display_name' => 'NOC Junior Tech',
                'name' => 'noc_junior_tech',
                'description' => 'Monitoring alerts only.',
                'color' => 'cyan',
                'permissions' => ['servers.view'],
            ]);

        $response->assertRedirect(route('admin.administration.roles'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'name' => 'noc_junior_tech',
            'display_name' => 'NOC Junior Tech',
        ]);
    }

    public function test_admin_can_update_role_and_sync_permissions(): void
    {
        $role = Role::where('name', 'test_role')->first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.administration.roles.update', $role->id), [
                'display_name' => 'Updated Lead Role',
                'description' => 'Updated description.',
                'color' => 'emerald',
                'permissions' => ['servers.view', 'servers.manage'],
            ]);

        $response->assertRedirect(route('admin.administration.roles'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'display_name' => 'Updated Lead Role',
            'color' => 'emerald',
        ]);

        $this->assertTrue($role->fresh()->hasPermissionTo('servers.manage'));
    }

    public function test_admin_cannot_delete_system_role(): void
    {
        $systemRole = Role::firstOrCreate(
            ['name' => 'super_admin_sys', 'guard_name' => 'web'],
            ['display_name' => 'System Superadmin', 'is_system' => true, 'color' => 'purple']
        );

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.roles.destroy', $systemRole->id));

        $response->assertRedirect(route('admin.administration.roles'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('roles', [
            'id' => $systemRole->id,
        ]);
    }

    public function test_admin_can_delete_unused_custom_role(): void
    {
        $role = Role::where('name', 'test_role')->first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.roles.destroy', $role->id));

        $response->assertRedirect(route('admin.administration.roles'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }
}
