<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_admin_can_view_users_directory(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data')
            ->has('stats')
        );
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.store'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'username' => 'johndoe',
                'email' => 'john.doe@example.com',
                'password' => 'SecurePass123!',
                'role' => 'client',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
            'role' => 'client',
        ]);
    }

    public function test_admin_can_view_user_profile(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.users.show', $client->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->has('user')
        );
    }

    public function test_admin_can_update_user(): void
    {
        $client = User::factory()->create([
            'first_name' => 'Jane',
            'role' => 'client',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.users.update', $client->id), [
                'first_name' => 'Jane Updated',
                'username' => $client->username,
                'email' => $client->email,
                'role' => 'reseller',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertEquals('Jane Updated', $client->fresh()->first_name);
        $this->assertEquals('reseller', $client->fresh()->role);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.toggle-status', $client->id));

        $response->assertRedirect();
        $this->assertEquals('suspended', $client->fresh()->status);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.reset-password', $client->id), [
                'password' => 'BrandNewPassword123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.users.destroy', $this->adminUser->id));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_other_user(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.users.destroy', $client->id));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted('users', ['id' => $client->id]);
    }

    public function test_admin_can_impersonate_client(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.users.impersonate', $client->id));

        $response->assertRedirect(route('client.dashboard'));
        $this->assertEquals($client->id, auth()->id());
        $this->assertEquals($this->adminUser->id, session('impersonated_by'));
    }

    public function test_user_can_stop_impersonating_and_return_to_admin(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this->actingAs($client)
            ->withSession(['impersonated_by' => $this->adminUser->id])
            ->post(route('impersonation.stop'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertEquals($this->adminUser->id, auth()->id());
    }
}
