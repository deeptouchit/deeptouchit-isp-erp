<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiUsersTest extends TestCase
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

        ApiUser::create([
            'user_id' => $this->adminUser->id,
            'name' => 'WHMCS Provisioning Bot',
            'email' => 'sa-whmcs-test@deeptouchhost.internal',
            'role' => 'service_account',
            'scopes' => ['accounts:manage', 'servers:read'],
            'ip_restrictions' => ['103.59.177.138'],
            'rate_limit_multiplier' => 2,
            'status' => 'active',
            'total_calls' => 1200,
        ]);
    }

    public function test_admin_can_view_api_users_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.users'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Users/Index')
            ->has('apiUsers')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_api_users_by_role(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.users', ['role' => 'service_account']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Users/Index')
            ->where('filters.role', 'service_account')
        );
    }

    public function test_admin_can_filter_api_users_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.users', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Users/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_api_users(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.users', ['search' => 'WHMCS']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Users/Index')
            ->where('filters.search', 'WHMCS')
        );
    }

    public function test_admin_can_create_new_api_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.users.store'), [
                'name' => 'Billing Microservice Bot',
                'email' => 'sa-billing-bot-test@deeptouchhost.internal',
                'role' => 'service_account',
                'scopes' => ['billing:manage'],
                'ip_restrictions' => ['103.26.247.144'],
                'rate_limit_multiplier' => 1,
            ]);

        $response->assertRedirect(route('admin.api.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('api_users', [
            'email' => 'sa-billing-bot-test@deeptouchhost.internal',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_api_user(): void
    {
        $user = ApiUser::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.api.users.update', $user->id), [
                'name' => 'WHMCS Provisioning Bot Updated',
                'email' => 'sa-whmcs-updated@deeptouchhost.internal',
                'role' => 'admin_bot',
                'scopes' => ['*'],
                'ip_restrictions' => ['127.0.0.1'],
                'rate_limit_multiplier' => 5,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.api.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('api_users', [
            'id' => $user->id,
            'name' => 'WHMCS Provisioning Bot Updated',
            'email' => 'sa-whmcs-updated@deeptouchhost.internal',
            'role' => 'admin_bot',
        ]);
    }

    public function test_admin_can_toggle_api_user_status(): void
    {
        $user = ApiUser::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.users.toggle-status', $user->id));

        $response->assertRedirect(route('admin.api.users'));
        $response->assertSessionHas('success');

        $this->assertEquals('suspended', $user->fresh()->status);
    }

    public function test_admin_can_delete_api_user(): void
    {
        $user = ApiUser::first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.api.users.destroy', $user->id));

        $response->assertRedirect(route('admin.api.users'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('api_users', [
            'id' => $user->id,
        ]);
    }
}
