<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIntegrationsTest extends TestCase
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

    public function test_admin_can_view_integrations_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.integrations'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Integrations/Index')
            ->has('integrations')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_integrations_by_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.integrations', ['category' => 'dns_cdn']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Integrations/Index')
            ->where('filters.category', 'dns_cdn')
        );
    }

    public function test_admin_can_filter_integrations_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.integrations', ['status' => 'connected']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Integrations/Index')
            ->where('filters.status', 'connected')
        );
    }

    public function test_admin_can_search_integrations(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.integrations', ['search' => 'Cloudflare']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Integrations/Index')
            ->where('filters.search', 'Cloudflare')
        );
    }

    public function test_admin_can_update_and_connect_integration(): void
    {
        $item = Integration::where('provider', 'cpanel_whm')->first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.api.integrations.update', $item->id), [
                'credentials' => [
                    'whm_url' => 'https://whm.example.com:2087',
                    'access_hash' => 'whmhash123456789',
                ],
                'settings' => [
                    'import_dns' => true,
                ],
            ]);

        $response->assertRedirect(route('admin.api.integrations'));
        $response->assertSessionHas('success');

        $this->assertEquals('connected', $item->fresh()->status);
    }

    public function test_admin_can_run_diagnostic_test_on_integration(): void
    {
        $item = Integration::where('provider', 'cloudflare')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.integrations.test', $item->id));

        $response->assertRedirect(route('admin.api.integrations'));
        $response->assertSessionHas('success');

        $this->assertNotNull($item->fresh()->last_health_check);
    }

    public function test_admin_can_trigger_manual_sync_on_integration(): void
    {
        $item = Integration::where('provider', 'telegram')->first();
        $initialEvents = $item->total_events;

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.integrations.sync', $item->id));

        $response->assertRedirect(route('admin.api.integrations'));
        $response->assertSessionHas('success');

        $this->assertGreaterThan($initialEvents, $item->fresh()->total_events);
    }

    public function test_admin_can_disconnect_integration(): void
    {
        $item = Integration::where('provider', 'github')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.integrations.disconnect', $item->id));

        $response->assertRedirect(route('admin.api.integrations'));
        $response->assertSessionHas('success');

        $this->assertEquals('disconnected', $item->fresh()->status);
    }
}
