<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Permission $permission;
    protected Role $customRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->permission = Permission::firstOrCreate(
            ['name' => 'test.permission.view', 'guard_name' => 'web'],
            ['category' => 'Infrastructure & Servers', 'description' => 'Test capability']
        );

        $this->customRole = Role::firstOrCreate(
            ['name' => 'test_custom_role', 'guard_name' => 'web'],
            ['display_name' => 'Test Role', 'is_system' => false, 'color' => 'blue']
        );
    }

    public function test_admin_can_view_permissions_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.permissions'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Permissions/Index')
            ->has('permissions')
            ->has('roles')
            ->has('categories')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_permissions_by_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.permissions', ['category' => 'Infrastructure & Servers']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Permissions/Index')
            ->where('filters.category', 'Infrastructure & Servers')
        );
    }

    public function test_admin_can_search_permissions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.permissions', ['search' => 'test.permission.view']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Permissions/Index')
            ->where('filters.search', 'test.permission.view')
        );
    }

    public function test_admin_can_create_custom_permission(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.permissions.store'), [
                'name' => 'docker.containers.restart',
                'category' => 'Infrastructure & Servers',
                'description' => 'Restart docker containers directly.',
            ]);

        $response->assertRedirect(route('admin.administration.permissions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('permissions', [
            'name' => 'docker.containers.restart',
            'category' => 'Infrastructure & Servers',
        ]);
    }

    public function test_admin_can_toggle_role_permission(): void
    {
        $this->assertFalse($this->customRole->hasPermissionTo($this->permission->name));

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.permissions.toggle-role'), [
                'permission_id' => $this->permission->id,
                'role_id' => $this->customRole->id,
            ]);

        $response->assertRedirect(route('admin.administration.permissions'));
        $response->assertSessionHas('success');

        $this->assertTrue($this->customRole->fresh()->hasPermissionTo($this->permission->name));
    }

    public function test_admin_cannot_toggle_super_admin_role_permission(): void
    {
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['display_name' => 'Super Administrator', 'is_system' => true]
        );

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.permissions.toggle-role'), [
                'permission_id' => $this->permission->id,
                'role_id' => $superAdmin->id,
            ]);

        $response->assertRedirect(route('admin.administration.permissions'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_delete_permission(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.permissions.destroy', $this->permission->id));

        $response->assertRedirect(route('admin.administration.permissions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('permissions', [
            'id' => $this->permission->id,
        ]);
    }
}
