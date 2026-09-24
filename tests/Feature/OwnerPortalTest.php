<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/owner/login');
        $response->assertStatus(200);
        $response->assertSee('Platform Owner Login');
    }

    public function test_owner_can_login_and_view_dashboard(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->post('/owner/login', [
            'email' => 'owner@somitysoft.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/owner/dashboard');
        $this->assertAuthenticatedAs($owner);

        $dashboard = $this->get('/owner/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('Overview Dashboard');
    }

    public function test_owner_can_access_all_dedicated_menus(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner_menu@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // 1. Payment Gateways (PGW)
        $pgw = $this->get('/owner/payment-gateways');
        $pgw->assertStatus(200);
        $pgw->assertSee('Payment Gateways');

        // 2. SMS Gateways
        $sms = $this->get('/owner/sms-gateways');
        $sms->assertStatus(200);
        $sms->assertSee('SMS Gateways');

        // 3. Mail & SMTP
        $mail = $this->get('/owner/mail-settings');
        $mail->assertStatus(200);
        $mail->assertSee('Mail & SMTP', false);

        // 4. Security & Backup
        $sec = $this->get('/owner/security-backup');
        $sec->assertStatus(200);
        $sec->assertSee('Enterprise Security Shield');

        // 5. General Settings
        $gen = $this->get('/owner/settings');
        $gen->assertStatus(200);
        $gen->assertSee('Platform Identity');
    }

    public function test_owner_can_trigger_database_backup(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner_backup@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $response = $this->post('/owner/security-backup/trigger', [
            'backup_type' => 'database',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_unauthorized_user_cannot_access_owner_dashboard(): void
    {
        $response = $this->get('/owner/dashboard');
        $response->assertRedirect('/owner/login');
    }

    public function test_owner_can_configure_and_test_payment_gateways(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner PGW',
            'email' => 'owner_pgw@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // Update settings
        $res = $this->post('/owner/payment-gateways', [
            'bkash_status' => '1',
            'bkash_app_key' => 'test_key',
            'bkash_app_secret' => 'test_secret',
            'currency_code' => 'BDT',
            'currency_symbol' => '৳',
        ]);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // Test gateway handshake
        $sim = $this->post('/owner/payment-gateways/test', [
            'gateway' => 'bkash',
            'amount' => 4500,
        ]);
        $sim->assertRedirect();
        $sim->assertSessionHas('test_result');
    }

    public function test_owner_can_manage_tenants_and_extend_subscription(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner Tenants',
            'email' => 'owner_tenants@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // Seed a plan
        $plan = \App\Models\SaasPlan::create([
            'code' => 'P1',
            'name' => 'P1',
            'slug' => 'p1',
            'monthly_price' => 600,
            'otc_charge' => 4000,
            'customer_limit' => 100,
            'is_active' => true,
        ]);

        // 1. View Tenants Index
        $res = $this->get('/owner/tenants');
        $res->assertStatus(200);
        $res->assertSee('ISP Tenants Directory');

        // 2. Provision new Tenant
        $postRes = $this->post('/owner/tenants', [
            'name' => 'FastNet ISP',
            'company_name' => 'FastNet Communications Ltd.',
            'slug' => 'fastnet',
            'domain' => 'fastnet.com',
            'phone' => '01711000000',
            'email' => 'contact@fastnet.com',
            'address' => 'Dhaka, Bangladesh',
            'saas_plan_id' => $plan->id,
            'admin_name' => 'FastNet Admin',
            'admin_email' => 'admin@fastnet.com',
            'admin_password' => 'secret123',
            'duration_months' => 3,
        ]);
        $postRes->assertRedirect('/owner/tenants');
        $postRes->assertSessionHas('success');

        $tenant = \App\Models\Tenant::where('slug', 'fastnet')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('active', $tenant->status);

        // 3. Extend Subscription
        $extendRes = $this->post("/owner/tenants/{$tenant->id}/extend-subscription", [
            'extension_type' => '6_months',
            'reactivate_if_suspended' => '1',
        ]);
        $extendRes->assertRedirect();
        $extendRes->assertSessionHas('success');

        // 4. Toggle Status
        $toggleRes = $this->post("/owner/tenants/{$tenant->id}/toggle-status");
        $toggleRes->assertRedirect();
        $this->assertEquals('suspended', $tenant->fresh()->status);
    }

    public function test_owner_can_manage_billing_and_invoices(): void
    {
        $owner = User::create([
            'name' => 'Platform Owner Billing',
            'email' => 'owner_billing@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $plan = \App\Models\SaasPlan::create([
            'code' => 'P2',
            'name' => 'P2',
            'slug' => 'p2',
            'monthly_price' => 1000,
            'otc_charge' => 4000,
            'customer_limit' => 200,
            'is_active' => true,
        ]);

        $tenant = \App\Models\Tenant::create([
            'name' => 'SkyNet ISP',
            'company_name' => 'SkyNet Ltd.',
            'slug' => 'skynet',
            'phone' => '01811000000',
            'email' => 'info@skynet.com',
            'saas_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // 1. Access Billing Index
        $res = $this->get('/owner/billing');
        $res->assertStatus(200);
        $res->assertSee('Billing & Invoices', false);

        // 2. Generate New Invoice
        $createRes = $this->post('/owner/billing/invoices', [
            'tenant_id' => $tenant->id,
            'saas_plan_id' => $plan->id,
            'amount' => 1000,
            'status' => 'paid',
            'payment_method' => 'bkash',
            'trx_id' => 'TRX8899AA',
            'auto_extend_tenant' => '1',
        ]);
        $createRes->assertRedirect('/owner/billing?tab=invoices');
        $createRes->assertSessionHas('success');

        $invoice = \App\Models\SaasInvoice::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(1000, $invoice->amount);

        // 3. Toggle Invoice Status
        $toggleRes = $this->post("/owner/billing/invoices/{$invoice->id}/toggle-status");
        $toggleRes->assertRedirect();
        $this->assertEquals('unpaid', $invoice->fresh()->status);

        // 4. Test Wallet Adjustment
        $walletRes = $this->post("/owner/billing/wallets/{$tenant->id}/adjust", [
            'type' => 'credit',
            'amount' => 500,
            'description' => 'Test deposit',
        ]);
        $walletRes->assertRedirect();
        $walletRes->assertSessionHas('success');

        // 5. Delete Invoice
        $deleteRes = $this->delete("/owner/billing/invoices/{$invoice->id}");
        $deleteRes->assertRedirect('/owner/billing?tab=invoices');
        $this->assertNull(\App\Models\SaasInvoice::find($invoice->id));
    }

    public function test_owner_can_access_dedicated_financial_pages(): void
    {
        $owner = User::create([
            'name' => 'Financial Admin',
            'email' => 'fin_admin@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // 1. Subscriptions
        $subRes = $this->get('/owner/subscriptions');
        $subRes->assertStatus(200);
        $subRes->assertSee('Tenant Subscriptions');

        // 2. Invoices
        $invRes = $this->get('/owner/invoices');
        $invRes->assertStatus(200);
        $invRes->assertSee('Billing & Invoices', false);

        // 3. Payments
        $payRes = $this->get('/owner/payments');
        $payRes->assertStatus(200);
        $payRes->assertSee('Payment Settlements');

        // 4. Transactions
        $trxRes = $this->get('/owner/transactions');
        $trxRes->assertStatus(200);
        $trxRes->assertSee('Gateway Transactions');

        // 5. Wallets
        $walRes = $this->get('/owner/wallets');
        $walRes->assertStatus(200);
        $walRes->assertSee('Prepaid Wallets');
    }
}

