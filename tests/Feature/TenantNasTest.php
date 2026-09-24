<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantNas;
use App\Models\TenantRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantNasTest extends TestCase
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
            'radius_secret' => 'fleet_radius_pass_456',
            'connection_type' => 'hybrid',
            'model' => 'CCR2004-16G-2S+',
            'ros_version' => '7.14.2',
            'status' => 'online',
            'is_active' => true,
        ]);
    }

    public function test_tenant_admin_can_view_nas_directory(): void
    {
        TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'router_id' => $this->router->id,
            'nasname' => '103.150.10.1',
            'shortname' => 'CORE-BRAS-01',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'my_super_vault_secret',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)->get(route('tenant.network.nas'));

        $response->assertStatus(200);
        $response->assertSee('NAS / Network Access Servers');
        $response->assertSee('CORE-BRAS-01');
        $response->assertSee('103.150.10.1');
        $response->assertSee('MikroTik RouterOS');
        $response->assertSee('FreeRADIUS 3.0 clients.conf');
    }

    public function test_tenant_admin_can_store_nas_client(): void
    {
        $payload = [
            'shortname' => 'DIST-BRAS-02',
            'nasname' => '10.200.0.1',
            'type' => 'mikrotik',
            'ports' => 1812,
            'coa_port' => 3799,
            'secret' => 'radius_secure_key_999',
            'server' => 'default',
            'community' => 'public',
            'description' => 'Secondary BRAS for Zone 2',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.network.nas.store'), $payload);

        $response->assertRedirect(route('tenant.network.nas'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenant_nas', [
            'tenant_id' => $this->tenant->id,
            'shortname' => 'DIST-BRAS-02',
            'nasname' => '10.200.0.1',
            'type' => 'mikrotik',
            'ports' => 1812,
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $nas = TenantNas::where('shortname', 'DIST-BRAS-02')->first();
        $this->assertNotNull($nas);
        $this->assertEquals('radius_secure_key_999', $nas->decrypted_secret);
    }

    public function test_tenant_admin_can_store_nas_with_fleet_router_link(): void
    {
        $payload = [
            'shortname' => $this->router->name,
            'nasname' => $this->router->ip_address,
            'type' => 'mikrotik',
            'ports' => 1812,
            'coa_port' => 3799,
            'secret' => 'test_secret_123',
            'router_id' => $this->router->id,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.network.nas.store'), $payload);

        $response->assertRedirect(route('tenant.network.nas'));

        $this->assertDatabaseHas('tenant_nas', [
            'tenant_id' => $this->tenant->id,
            'router_id' => $this->router->id,
            'nasname' => $this->router->ip_address,
        ]);
    }

    public function test_nas_name_must_be_unique_per_tenant(): void
    {
        TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '10.10.10.1',
            'shortname' => 'BRAS-01',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'secret123',
            'coa_port' => 3799,
        ]);

        $payload = [
            'shortname' => 'DUPLICATE-IP',
            'nasname' => '10.10.10.1',
            'type' => 'cisco',
            'ports' => 1812,
            'coa_port' => 3799,
            'secret' => 'secret456',
        ];

        $response = $this->actingAs($this->tenantAdmin)
            ->post(route('tenant.network.nas.store'), $payload);

        $response->assertSessionHasErrors(['nasname']);
    }

    public function test_tenant_admin_can_update_nas_client(): void
    {
        $nas = TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '172.16.0.1',
            'shortname' => 'CISCO-AGG-01',
            'type' => 'cisco',
            'ports' => 1812,
            'secret' => 'original_secret',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->put(route('tenant.network.nas.update', $nas->id), [
                'shortname' => 'CISCO-AGG-MODIFIED',
                'nasname' => '172.16.0.1',
                'type' => 'juniper',
                'ports' => 1812,
                'coa_port' => 3799,
                'secret' => '', // leave empty to keep original
                'is_active' => '1',
            ]);

        $response->assertSessionHas('success');

        $nas->refresh();
        $this->assertEquals('CISCO-AGG-MODIFIED', $nas->shortname);
        $this->assertEquals('juniper', $nas->type);
        $this->assertEquals('original_secret', $nas->decrypted_secret);
    }

    public function test_tenant_admin_can_toggle_nas_status_via_ajax(): void
    {
        $nas = TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '192.168.1.1',
            'shortname' => 'ROUTER-TOGGLE',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'secret123',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->patchJson(route('tenant.network.nas.toggle-status', $nas->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_active' => false,
        ]);

        $this->assertFalse($nas->fresh()->is_active);

        // Toggle back to active
        $response2 = $this->actingAs($this->tenantAdmin)
            ->patchJson(route('tenant.network.nas.toggle-status', $nas->id));

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($nas->fresh()->is_active);
    }

    public function test_tenant_admin_can_test_coa_socket(): void
    {
        $nas = TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '127.0.0.1',
            'shortname' => 'TEST-COA-NAS',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'test_secret',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->postJson(route('tenant.network.nas.test-coa', $nas->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment([
            'message' => 'RFC 3576 CoA endpoint verified on 127.0.0.1:3799 (Secret confirmed).',
        ]);
    }

    public function test_tenant_admin_can_download_freeradius_clients_conf(): void
    {
        TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '103.150.10.1',
            'shortname' => 'CORE_BRAS_01',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'radius_pwd_secure',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->get(route('tenant.network.nas.config'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $content = $response->getContent();
        $this->assertStringContainsString('client CORE_BRAS_01', $content);
        $this->assertStringContainsString('ipaddr = 103.150.10.1', $content);
        $this->assertStringContainsString('secret = radius_pwd_secure', $content);
        $this->assertStringContainsString('coa_server = 103.150.10.1:3799', $content);
    }

    public function test_tenant_isolation_prevents_access_to_other_tenant_nas(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other ISP',
            'company_name' => 'Other ISP Ltd',
            'slug' => 'otherisp',
            'status' => 'active',
        ]);

        $otherNas = TenantNas::create([
            'tenant_id' => $otherTenant->id,
            'nasname' => '10.99.99.1',
            'shortname' => 'OTHER-NAS',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'other_secret',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        // Attempting to delete other tenant's NAS
        $response = $this->actingAs($this->tenantAdmin)
            ->delete(route('tenant.network.nas.destroy', $otherNas->id));

        $response->assertStatus(404);
        $this->assertDatabaseHas('tenant_nas', ['id' => $otherNas->id]);
    }

    public function test_tenant_admin_can_delete_nas(): void
    {
        $nas = TenantNas::create([
            'tenant_id' => $this->tenant->id,
            'nasname' => '10.5.5.1',
            'shortname' => 'TEMP-NAS',
            'type' => 'mikrotik',
            'ports' => 1812,
            'secret' => 'temp_secret',
            'coa_port' => 3799,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantAdmin)
            ->delete(route('tenant.network.nas.destroy', $nas->id));

        $response->assertRedirect(route('tenant.network.nas'));
        $this->assertDatabaseMissing('tenant_nas', ['id' => $nas->id]);
    }
}
