<?php

namespace Tests\Feature;

use App\Models\ApiRateLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiRateLimitsTest extends TestCase
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

    public function test_admin_can_view_rate_limits_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.rate-limits'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/RateLimits/Index')
            ->has('policies')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_rate_limits_by_scope(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.rate-limits', ['scope' => 'global_ip']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/RateLimits/Index')
            ->where('filters.scope', 'global_ip')
        );
    }

    public function test_admin_can_filter_rate_limits_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.rate-limits', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/RateLimits/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_rate_limits(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.rate-limits', ['search' => 'Anonymous IP']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/RateLimits/Index')
            ->where('filters.search', 'Anonymous IP')
        );
    }

    public function test_admin_can_create_new_rate_limit_policy(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.rate-limits.store'), [
                'name' => 'Metrics Scraping Throttle',
                'scope_type' => 'endpoint_specific',
                'endpoint_pattern' => '/api/v1/metrics/*',
                'requests_per_minute' => 600,
                'burst_capacity' => 50,
                'action_on_breach' => 'delay_throttle',
                'ban_duration_minutes' => 0,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.api.rate-limits'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('api_rate_limits', [
            'name' => 'Metrics Scraping Throttle',
            'requests_per_minute' => 600,
        ]);
    }

    public function test_admin_can_update_rate_limit_policy(): void
    {
        $policy = ApiRateLimit::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.api.rate-limits.update', $policy->id), [
                'name' => 'Public Anonymous IP Baseline Updated',
                'scope_type' => 'global_ip',
                'endpoint_pattern' => '/api/*',
                'requests_per_minute' => 90,
                'burst_capacity' => 15,
                'action_on_breach' => 'http_429',
                'ban_duration_minutes' => 15,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.api.rate-limits'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('api_rate_limits', [
            'id' => $policy->id,
            'name' => 'Public Anonymous IP Baseline Updated',
            'requests_per_minute' => 90,
        ]);
    }

    public function test_admin_can_toggle_rate_limit_status(): void
    {
        $policy = ApiRateLimit::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.rate-limits.toggle-status', $policy->id));

        $response->assertRedirect(route('admin.api.rate-limits'));
        $response->assertSessionHas('success');

        $this->assertEquals('disabled', $policy->fresh()->status);
    }

    public function test_admin_can_delete_rate_limit_policy(): void
    {
        $policy = ApiRateLimit::first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.api.rate-limits.destroy', $policy->id));

        $response->assertRedirect(route('admin.api.rate-limits'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('api_rate_limits', [
            'id' => $policy->id,
        ]);
    }
}
