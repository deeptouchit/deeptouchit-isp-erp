<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdministratorsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'Super Administrator',
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_admin_can_view_administrators_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.administrators'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Administrators/Index')
            ->has('administrators')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_administrators_by_role(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.administrators', ['role' => 'Super Administrator']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Administrators/Index')
            ->where('filters.role', 'Super Administrator')
        );
    }

    public function test_admin_can_filter_administrators_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.administrators', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Administrators/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_administrators(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.administrators', ['search' => $this->adminUser->email]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Administrators/Index')
            ->where('filters.search', $this->adminUser->email)
        );
    }

    public function test_admin_can_create_new_administrator(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.administrators.store'), [
                'first_name' => 'Kazi',
                'last_name' => 'Anwar',
                'email' => 'kazi@deeptouchhost.com',
                'username' => 'kazi_sec',
                'password' => 'SecurePass123!@#',
                'phone' => '+8801700000000',
                'admin_role' => 'Security Officer',
                'ip_allowlist' => ['103.59.177.138'],
                'two_factor_enforced' => true,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'kazi@deeptouchhost.com',
            'admin_role' => 'Security Officer',
        ]);
    }

    public function test_admin_can_update_administrator(): void
    {
        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'Systems Engineer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.administration.administrators.update', $targetAdmin->id), [
                'first_name' => 'UpdatedFirst',
                'last_name' => 'UpdatedLast',
                'email' => $targetAdmin->email,
                'username' => $targetAdmin->username,
                'admin_role' => 'Security Officer',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $targetAdmin->id,
            'first_name' => 'UpdatedFirst',
            'admin_role' => 'Security Officer',
        ]);
    }

    public function test_admin_can_toggle_administrator_status(): void
    {
        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'Systems Engineer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.administrators.toggle-status', $targetAdmin->id));

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('success');

        $this->assertEquals('suspended', $targetAdmin->fresh()->status);
    }

    public function test_admin_can_reset_administrator_2fa(): void
    {
        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'two_factor_secret' => 'secret123',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.administrators.reset-2fa', $targetAdmin->id));

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('success');

        $this->assertNull($targetAdmin->fresh()->two_factor_secret);
        $this->assertNull($targetAdmin->fresh()->two_factor_confirmed_at);
    }

    public function test_admin_can_delete_administrator(): void
    {
        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.administrators.destroy', $targetAdmin->id));

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', [
            'id' => $targetAdmin->id,
        ]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.administrators.destroy', $this->adminUser->id));

        $response->assertRedirect(route('admin.administration.administrators'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
            'deleted_at' => null,
        ]);
    }
}
