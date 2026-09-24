<?php

namespace Tests\Feature;

use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TenantAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $tenantAdmin;
    protected SaasPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SaasPlan::create([
            'name' => 'Carrier P5',
            'slug' => 'carrier-p5',
            'customer_limit' => 500,
            'otc_charge' => 5000,
            'monthly_price' => 2000,
            'olt_limit' => 2,
            'mikrotik_limit' => 4,
            'reseller_limit' => 10,
            'allow_radius' => true,
            'allow_wireguard' => true,
            'allow_snmp_monitoring' => true,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'SpeedNet Telecom',
            'company_name' => 'SpeedNet Communications Ltd.',
            'slug' => 'speednet',
            'phone' => '01711223344',
            'email' => 'contact@speednet.com',
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
            'subscription_expires_at' => now()->addDays(30),
        ]);

        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kamal Hossain',
            'email' => 'admin@speednet.com',
            'phone' => '01711223344',
            'password' => bcrypt('password123'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);
    }

    public function test_guest_can_view_tenant_login_page(): void
    {
        $response = $this->get(route('tenant.login'));
        $response->assertStatus(200);
        $response->assertSee('ISP Tenant Portal');
        $response->assertSee('Sign In to ISP Admin Portal');
    }

    public function test_guest_can_view_branded_tenant_login_page(): void
    {
        $response = $this->get(route('tenant.slug.login', ['slug' => 'speednet']));
        $response->assertStatus(200);
        $response->assertSee('SpeedNet Communications Ltd.');
    }

    public function test_tenant_admin_can_login_with_valid_email(): void
    {
        $response = $this->post(route('tenant.login.submit'), [
            'email' => 'admin@speednet.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('tenant.dashboard'));
        $this->assertAuthenticatedAs($this->tenantAdmin);
    }

    public function test_tenant_admin_can_login_with_phone_number(): void
    {
        $response = $this->post(route('tenant.login.submit'), [
            'email' => '01711223344',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('tenant.dashboard'));
        $this->assertAuthenticatedAs($this->tenantAdmin);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post(route('tenant.login.submit'), [
            'email' => 'admin@speednet.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_rate_limiting_locks_out_after_5_failures(): void
    {
        RateLimiter::clear(strtolower('admin@speednet.com') . '|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('tenant.login.submit'), [
                'email' => 'admin@speednet.com',
                'password' => 'wrong_pass',
            ]);
        }

        // 6th attempt should be blocked
        $response = $this->post(route('tenant.login.submit'), [
            'email' => 'admin@speednet.com',
            'password' => 'wrong_pass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(str_contains(session('errors')->first('email'), 'Too many login attempts'));
    }

    public function test_suspended_tenant_is_redirected_to_billing_suspended(): void
    {
        $this->tenant->update(['status' => 'suspended']);

        $response = $this->post(route('tenant.login.submit'), [
            'email' => 'admin@speednet.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('tenant.billing.suspended'));
        $this->assertAuthenticatedAs($this->tenantAdmin);
    }

    public function test_authenticated_tenant_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->get(route('tenant.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('SpeedNet Communications Ltd.');
        $response->assertSee('Carrier P5');
        $response->assertSee('ISP Admin Console');
    }

    public function test_tenant_admin_can_logout(): void
    {
        $this->actingAs($this->tenantAdmin);

        $response = $this->post(route('tenant.logout'));
        $response->assertRedirect(route('tenant.login'));
        $this->assertGuest();
    }

    public function test_authenticated_tenant_admin_can_access_billing_and_support_with_layout(): void
    {
        $this->actingAs($this->tenantAdmin);

        // Billing Dashboard
        $billingResp = $this->get(route('tenant.billing.dashboard'));
        $billingResp->assertStatus(200);
        $billingResp->assertSee('Billing Overview');
        $billingResp->assertSee('ISP Admin Console');

        // Billing Invoices
        $invoicesResp = $this->get(route('tenant.billing.invoices'));
        $invoicesResp->assertStatus(200);
        $invoicesResp->assertSee('Subscription Invoices History');

        // Support Tickets
        $supportResp = $this->get(route('tenant.tickets.index'));
        $supportResp->assertStatus(200);
        $supportResp->assertSee('Your Support Tickets');
    }

    public function test_authenticated_tenant_admin_can_access_network_routes(): void
    {
        $this->actingAs($this->tenantAdmin);

        $routes = [
            'tenant.network.mikrotik' => 'MikroTik Router Gateways',
            'tenant.network.nas' => 'Network Access Servers',
            'tenant.network.olt' => 'OLT Devices',
            'tenant.network.onu' => 'ONU / ONT Terminal Management',
            'tenant.network.ip-pools' => 'IP Pools',
            'tenant.network.vlans' => 'Virtual LANs',
            'tenant.network.routing' => 'Dynamic Routing',
            'tenant.network.map' => 'Interactive Network Topology',
        ];

        foreach ($routes as $route => $expectedText) {
            $resp = $this->get(route($route));
            $resp->assertStatus(200);
            $resp->assertSee($expectedText);
            $resp->assertSee('ISP Admin Console');
        }
    }

    public function test_generic_login_redirects_to_admin_login(): void
    {
        $response = $this->get('/login');
        $response->assertRedirect(route('tenant.login'));
    }
}
