<?php

namespace Tests\Feature;

use App\Models\NetworkAuditLog;
use App\Models\SaasPlan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantOlt;
use App\Models\TenantRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class OwnerNetworkEngineTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Platform Owner Admin',
            'email' => 'owner_network@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $plan = SaasPlan::create([
            'name' => 'Enterprise Carrier P10',
            'slug' => 'carrier-p10',
            'customer_limit' => 2000,
            'otc_charge' => 10000,
            'monthly_price' => 5000,
            'olt_limit' => 5,
            'mikrotik_limit' => 10,
            'reseller_limit' => 50,
            'allow_radius' => true,
            'allow_wireguard' => true,
            'allow_snmp_monitoring' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Apex Broadband',
            'company_name' => 'Apex Broadband Limited',
            'slug' => 'apex-isp',
            'saas_plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_access_network_engines_hub(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('owner.network-engines.index'));
        $response->assertStatus(200);
        $response->assertSee('Carrier Network Engines Hub');
        $response->assertSee('MikroTik Routers');
        $response->assertSee('OLT PON Hardware');
        $response->assertSee('FreeRADIUS AAA');
        $response->assertSee('WireGuard Tunnel');
    }

    public function test_owner_can_view_and_update_global_network_settings(): void
    {
        $this->actingAs($this->owner);

        $settingsPage = $this->get(route('owner.network-engines.settings'));
        $settingsPage->assertStatus(200);
        $settingsPage->assertSee('Global Network Infrastructure', false);

        $payload = [
            'network_default_api_port' => 8728,
            'network_default_ssl_api_port' => 8729,
            'network_api_timeout_seconds' => 10,
            'network_api_least_privilege_group' => 'somitysoft_api',
            'network_radius_master_host' => '10.50.0.1',
            'network_radius_master_auth_port' => 1812,
            'network_radius_master_acct_port' => 1813,
            'network_radius_replica_host' => '10.50.0.2',
            'network_radius_coa_port' => 3799,
            'network_radius_default_secret' => 'super_secret_radius_key',
            'network_radius_interim_interval' => 5,
            'network_olt_default_snmp_port' => 161,
            'network_olt_default_snmp_community' => 'public_somitysoft',
            'network_olt_snmp_timeout_ms' => 3000,
            'network_olt_polling_interval_minutes' => 10,
            'network_olt_optical_rx_warning_dbm' => -25.5,
            'network_olt_optical_rx_critical_dbm' => -28.5,
            'network_wireguard_enabled' => 1,
            'network_wireguard_endpoint' => 'vpn.somitysoft.com:51820',
            'network_wireguard_listen_port' => 51820,
            'network_wireguard_server_pubkey' => 'SERVER_PUBKEY_TEST',
            'network_wireguard_server_privkey' => 'SERVER_PRIVKEY_TEST',
            'network_wireguard_subnet_pool' => '10.50.0.0/16',
            'network_monitoring_queue_concurrency' => 16,
            'network_auto_coa_on_due_expired' => 1,
            'network_auto_restore_on_payment' => 1,
        ];

        $updateResponse = $this->post(route('owner.network-engines.settings.update'), $payload);
        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        // Verify that secrets are encrypted in the settings table
        $encryptedSecret = Setting::where('key', 'network_radius_default_secret')->value('value');
        $this->assertNotEquals('super_secret_radius_key', $encryptedSecret);
        $this->assertEquals('super_secret_radius_key', Crypt::decryptString($encryptedSecret));

        // Verify that an immutable Network Audit Log was recorded
        $this->assertDatabaseHas('network_audit_logs', [
            'action' => 'GLOBAL_NETWORK_CONFIG_UPDATE',
            'status' => 'success',
            'device_type' => 'system',
        ]);
    }

    public function test_owner_can_generate_mikrotik_bootstrap_script(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson(route('owner.network-engines.generate-script'), [
            'tenant_slug' => $this->tenant->slug,
            'router_name' => 'Core-CCR2004',
            'username' => 'somitysoft_api',
            'password' => 'SafePass1234!',
            'api_port' => 8728,
            'radius_secret' => 'radius_secret_123',
            'wireguard_ip' => '10.50.1.2',
            'wireguard_key' => 'TEST_WG_PRIVATE_KEY',
            'ros_version' => 'v7',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $script = $response->json('script');
        $this->assertStringContainsString('/user group add name="somitysoft_api"', $script);
        $this->assertStringContainsString('/radius add service=ppp,hotspot', $script);
        $this->assertStringContainsString('/interface wireguard add name="wg-somitysoft"', $script);
    }

    public function test_router_and_olt_credentials_are_encrypted_in_vault(): void
    {
        // 1. Test Router Vault
        $router = TenantRouter::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'HQ Core Router',
            'model' => 'CCR2004',
            'ros_version' => 'v7.14',
            'connection_type' => 'hybrid',
            'ip_address' => '10.50.1.2',
            'api_port' => 8728,
            'username' => 'api_controller',
            'password' => 'plaintext_mkt_password',
            'radius_secret' => 'plaintext_radius_secret',
            'status' => 'online',
        ]);

        // Direct DB check: must not contain plaintext password
        $rawRouter = \DB::table('tenant_routers')->where('id', $router->id)->first();
        $this->assertNotEquals('plaintext_mkt_password', $rawRouter->password);
        $this->assertNotEquals('plaintext_radius_secret', $rawRouter->radius_secret);

        // Accessor check: decrypted properly
        $this->assertEquals('plaintext_mkt_password', $router->decrypted_password);
        $this->assertEquals('plaintext_radius_secret', $router->decrypted_radius_secret);

        // 2. Test OLT Vault
        $olt = TenantOlt::create([
            'tenant_id' => $this->tenant->id,
            'router_id' => $router->id,
            'name' => 'Main Headend VSOL',
            'brand' => 'vsol',
            'model' => 'V1600G1',
            'ip_address' => '192.168.10.1',
            'snmp_port' => 161,
            'snmp_community' => 'secret_snmp_community',
            'telnet_password' => 'secret_telnet_pass',
            'status' => 'online',
        ]);

        $rawOlt = \DB::table('tenant_olts')->where('id', $olt->id)->first();
        $this->assertNotEquals('secret_snmp_community', $rawOlt->snmp_community);
        $this->assertNotEquals('secret_telnet_pass', $rawOlt->telnet_password);

        $this->assertEquals('secret_snmp_community', $olt->decrypted_snmp_community);
        $this->assertEquals('secret_telnet_pass', $olt->decrypted_telnet_password);
    }

    public function test_owner_can_access_network_audit_logs(): void
    {
        $this->actingAs($this->owner);

        NetworkAuditLog::record(
            action: 'ROUTER_REBOOT',
            deviceType: 'router',
            deviceName: 'Core-CCR2004',
            status: 'success',
            command: '/system reboot',
            summary: 'Router initiated reboot cleanly',
            tenantId: $this->tenant->id
        );

        $response = $this->get(route('owner.network-engines.audit-logs'));
        $response->assertStatus(200);
        $response->assertSee('Immutable Network Audit Trail');
        $response->assertSee('ROUTER_REBOOT');
        $response->assertSee('Core-CCR2004');
    }

    public function test_owner_can_run_engine_readiness_probe(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson(route('owner.network-engines.test-readiness'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'checks' => [
                '*' => ['engine', 'name', 'status', 'details'],
            ],
        ]);
    }

    public function test_owner_can_generate_wireguard_keypair(): void
    {
        $this->actingAs($this->owner);

        $response = $this->postJson(route('owner.network-engines.generate-keypair'));
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'public_key',
            'private_key',
        ]);

        $this->assertNotEmpty($response->json('public_key'));
        $this->assertNotEmpty($response->json('private_key'));
    }

    public function test_owner_can_filter_and_export_audit_logs_csv(): void
    {
        $this->actingAs($this->owner);

        NetworkAuditLog::record(
            action: 'RADIUS_COA_DISCONNECT',
            deviceType: 'radius',
            deviceName: 'FreeRADIUS-Cluster',
            status: 'success',
            command: 'radclient -x 10.50.0.1:3799 disconnect',
            summary: 'Dispatched CoA packet for session timeout',
            tenantId: $this->tenant->id
        );

        // Test filtering
        $filterResponse = $this->get(route('owner.network-engines.audit-logs', [
            'device_type' => 'radius',
            'status' => 'success',
            'search' => 'FreeRADIUS',
        ]));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('RADIUS_COA_DISCONNECT');
        $filterResponse->assertSee('FreeRADIUS-Cluster');

        // Test CSV Export stream
        $csvResponse = $this->get(route('owner.network-engines.audit-logs', ['export' => 'csv']));
        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('RADIUS_COA_DISCONNECT', $csvResponse->streamedContent());
    }
}
