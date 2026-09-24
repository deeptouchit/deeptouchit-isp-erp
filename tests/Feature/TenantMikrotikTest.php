<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantMikrotikTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $tenantAdmin;
    protected TenantRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'SpeedNet Online ISP',
            'company_name' => 'SpeedNet Communications Ltd.',
            'slug' => 'speednet',
            'status' => 'active',
            'subscription_expires_at' => now()->addDays(30),
        ]);

        $this->tenantAdmin = User::create([
            'name' => 'SpeedNet Admin',
            'email' => 'admin@speednet.com',
            'password' => bcrypt('password123'),
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        $this->router = TenantRouter::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Core-BNG-CCR2004',
            'ip_address' => '103.150.10.1',
            'api_port' => 8728,
            'use_ssl' => false,
            'username' => 'somitysoft_api',
            'password' => 'router_secret_123',
            'connection_type' => 'hybrid',
            'model' => 'CCR2004-16G-2S+',
            'ros_version' => '7.14.2',
            'status' => 'online',
            'cpu_load' => 12,
            'free_memory' => 3800000000,
            'total_memory' => 4294967296,
            'uptime' => '2w3d',
            'is_active' => true,
            'last_sync_at' => now(),
        ]);
    }

    public function test_tenant_admin_can_view_mikrotik_fleet(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->get(route('tenant.network.mikrotik'));
        $response->assertStatus(200);
        $response->assertSee('MikroTik Router Fleet');
        $response->assertSee('Core-BNG-CCR2004');
        $response->assertSee('103.150.10.1');
        $response->assertSee('CCR2004-16G-2S+');
    }

    public function test_tenant_admin_can_create_new_router(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->post(route('tenant.network.mikrotik.store'), [
            'name' => 'Uttara-POP-RB4011',
            'ip_address' => '103.150.10.2',
            'api_port' => 8728,
            'use_ssl' => 0,
            'username' => 'somitysoft_api',
            'password' => 'pop_secret_456',
            'connection_type' => 'hybrid',
            'radius_secret' => 'radius_pwd_123',
            'model' => 'RB4011iGS+',
        ]);

        $response->assertRedirect(route('tenant.network.mikrotik'));
        $this->assertDatabaseHas('tenant_routers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Uttara-POP-RB4011',
            'ip_address' => '103.150.10.2',
        ]);
    }

    public function test_cannot_create_router_with_invalid_data(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->post(route('tenant.network.mikrotik.store'), [
            'name' => '',
            'ip_address' => '',
            'api_port' => 999999, // Invalid port
        ]);

        $response->assertSessionHasErrors(['name', 'ip_address', 'api_port', 'username', 'password', 'connection_type']);
    }

    public function test_tenant_admin_can_view_router_deep_dive_monitoring(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->get(route('tenant.network.mikrotik.show', $this->router->id));
        $response->assertStatus(200);
        $response->assertSee('Core-BNG-CCR2004');
        $response->assertSee('CPU Utilization');
        $response->assertSee('RAM Available');
        $response->assertSee('API Latency (RTT)');
        $response->assertSee('Hardware & RouterOS Specifications', false);
        $response->assertDontSee('Real-Time Telemetry Stream');
        $response->assertDontSee('Heartbeat buffer');
        // Assert that the sidebar menu for MikroTik Routers is active
        $response->assertSee('border-blue-200 bg-blue-50/80 text-blue-600 font-bold border-l-4 border-l-blue-600');
    }

    public function test_tenant_admin_can_fetch_live_telemetry_stream(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->get(route('tenant.network.mikrotik.telemetry', $this->router->id));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'status',
            'cpu_load',
            'free_memory',
            'total_memory',
            'uptime',
            'model',
            'ros_version',
        ]);
    }

    public function test_tenant_admin_can_update_router(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->put(route('tenant.network.mikrotik.update', $this->router->id), [
            'name' => 'Core-BNG-CCR2004-Renamed',
            'ip_address' => '103.150.10.99',
            'api_port' => 8729,
            'use_ssl' => 1,
            'username' => 'admin_ssl',
            'connection_type' => 'api',
            'model' => 'CCR2004-16G-2S+',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_routers', [
            'id' => $this->router->id,
            'name' => 'Core-BNG-CCR2004-Renamed',
            'ip_address' => '103.150.10.99',
            'api_port' => 8729,
            'use_ssl' => 1,
        ]);
    }

    public function test_tenant_admin_can_delete_router(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->delete(route('tenant.network.mikrotik.destroy', $this->router->id));
        $response->assertRedirect(route('tenant.network.mikrotik'));
        $this->assertDatabaseMissing('tenant_routers', [
            'id' => $this->router->id,
        ]);
    }

    public function test_tenant_isolation_prevents_accessing_other_tenants_router(): void
    {
        // Tenant 2
        $otherTenant = Tenant::create([
            'name' => 'Other ISP',
            'company_name' => 'Other ISP Ltd.',
            'slug' => 'otherisp',
            'status' => 'active',
        ]);
        $otherRouter = TenantRouter::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Secret-Other-Router',
            'ip_address' => '10.0.0.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'secret',
            'connection_type' => 'api',
        ]);

        $this->actingAs($this->tenantAdmin);

        // Attempt to show other tenant's router
        $response = $this->get(route('tenant.network.mikrotik.show', $otherRouter->id));
        $response->assertStatus(404);

        // Attempt to delete other tenant's router
        $deleteResponse = $this->delete(route('tenant.network.mikrotik.destroy', $otherRouter->id));
        $deleteResponse->assertStatus(404);
        $this->assertDatabaseHas('tenant_routers', ['id' => $otherRouter->id]);
    }

    public function test_tenant_admin_can_toggle_router_enabled_and_disabled_status(): void
    {
        $this->actingAs($this->tenantAdmin);

        // Initially active
        $this->assertTrue($this->router->is_active);

        // 1. Toggle to disabled
        $response = $this->patch(route('tenant.network.mikrotik.toggle-status', $this->router->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_routers', [
            'id' => $this->router->id,
            'is_active' => false,
            'status' => 'offline',
        ]);

        // 2. Disabled router shows DISABLED badge on index
        $indexResponse = $this->get(route('tenant.network.mikrotik'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('DISABLED');

        // 3. Telemetry on disabled router returns disabled response
        $telemetryResponse = $this->get(route('tenant.network.mikrotik.telemetry', $this->router->id));
        $telemetryResponse->assertStatus(200);
        $telemetryResponse->assertJson([
            'success' => false,
            'is_active' => false,
            'status' => 'disabled',
        ]);

        // 4. Toggle back to enabled
        $enableResponse = $this->patch(route('tenant.network.mikrotik.toggle-status', $this->router->id));
        $enableResponse->assertRedirect();
        $this->assertDatabaseHas('tenant_routers', [
            'id' => $this->router->id,
            'is_active' => true,
        ]);
    }
}
