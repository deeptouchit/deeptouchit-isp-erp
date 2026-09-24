<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminApiKeysTest extends TestCase
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

        ApiKey::create([
            'user_id' => $this->adminUser->id,
            'name' => 'WHMCS Automation Provisioner',
            'key_prefix' => 'sh_live_8f3a9e...',
            'secret_hash' => Hash::make('sh_live_8f3a9e1029384756abcdef1234567890'),
            'abilities' => ['accounts:manage', 'servers:read'],
            'ip_allowlist' => ['103.59.177.138'],
            'rate_limit_per_minute' => 120,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_api_keys_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.keys'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Keys/Index')
            ->has('apiKeys')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_api_keys_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.keys', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Keys/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_api_keys(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.keys', ['search' => 'WHMCS']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Keys/Index')
            ->where('filters.search', 'WHMCS')
        );
    }

    public function test_admin_can_create_new_api_key_with_token_generation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.keys.store'), [
                'name' => 'Mobile App Integration',
                'abilities' => ['*'],
                'ip_allowlist' => ['103.26.247.144'],
                'rate_limit_per_minute' => 60,
                'expires_days' => 90,
            ]);

        $response->assertRedirect(route('admin.api.keys'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('generated_token');

        $this->assertDatabaseHas('api_keys', [
            'name' => 'Mobile App Integration',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_api_key(): void
    {
        $key = ApiKey::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.api.keys.update', $key->id), [
                'name' => 'WHMCS Provisioner Updated',
                'abilities' => ['servers:read'],
                'ip_allowlist' => ['127.0.0.1'],
                'rate_limit_per_minute' => 300,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.api.keys'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('api_keys', [
            'id' => $key->id,
            'name' => 'WHMCS Provisioner Updated',
            'rate_limit_per_minute' => 300,
        ]);
    }

    public function test_admin_can_regenerate_api_key_secret(): void
    {
        $key = ApiKey::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.keys.regenerate', $key->id));

        $response->assertRedirect(route('admin.api.keys'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('generated_token');
    }

    public function test_admin_can_revoke_api_key(): void
    {
        $key = ApiKey::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.keys.revoke', $key->id));

        $response->assertRedirect(route('admin.api.keys'));
        $response->assertSessionHas('success');

        $this->assertEquals('revoked', $key->fresh()->status);
    }

    public function test_admin_can_delete_api_key(): void
    {
        $key = ApiKey::first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.api.keys.destroy', $key->id));

        $response->assertRedirect(route('admin.api.keys'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('api_keys', [
            'id' => $key->id,
        ]);
    }
}
