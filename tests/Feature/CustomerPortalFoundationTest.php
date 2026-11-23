<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerAccount;
use App\Models\TenantCustomerInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPortalFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_login_is_scoped_to_tenant_and_uses_portal_password(): void
    {
        $tenant = $this->createTenant('alpha-isp');
        $customer = $this->createCustomer($tenant, 'alpha-customer', '01700000000', 'pppoe-secret');
        TenantCustomerAccount::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'login_identifier' => '01700000000',
            'password' => Hash::make('portal-secret'),
            'status' => 'active',
        ]);

        $this->post(route('customer.portal.clean.login.submit', $tenant), [
            'login_identifier' => '01700000000',
            'password' => 'pppoe-secret',
        ])->assertSessionHasErrors('login_identifier');

        $this->post(route('customer.portal.clean.login.submit', $tenant), [
            'login_identifier' => '01700000000',
            'password' => 'portal-secret',
        ])->assertRedirect(route('customer.portal.clean.dashboard', $tenant));

        $this->assertAuthenticated('customer');
    }

    public function test_customer_cannot_use_another_tenants_login_identifier(): void
    {
        $tenant = $this->createTenant('alpha-isp');
        $otherTenant = $this->createTenant('beta-isp');
        $customer = $this->createCustomer($otherTenant, 'beta-customer', '01800000000', 'pppoe-secret');
        TenantCustomerAccount::create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $customer->id,
            'login_identifier' => 'same@example.com',
            'password' => Hash::make('portal-secret'),
            'status' => 'active',
        ]);

        $this->post(route('customer.portal.login.submit', $tenant), [
            'login_identifier' => 'same@example.com',
            'password' => 'portal-secret',
        ])->assertSessionHasErrors('login_identifier');
    }

    public function test_customer_payment_intent_is_locked_to_owned_invoice_and_idempotent(): void
    {
        $tenant = $this->createTenant('alpha-isp');
        $customer = $this->createCustomer($tenant, 'alpha-customer', '01700000000', 'pppoe-secret');
        TenantCustomerAccount::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'login_identifier' => '01700000000',
            'password' => Hash::make('portal-secret'),
            'status' => 'active',
        ]);
        $invoice = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-PORTAL-1',
            'billing_month' => '2026-11',
            'amount' => 500,
            'total_payable' => 500,
            'total_amount' => 500,
            'due_amount' => 500,
            'status' => 'unpaid',
        ]);

        $this->post(route('customer.portal.login.submit', $tenant), [
            'login_identifier' => '01700000000',
            'password' => 'portal-secret',
        ]);

        $payload = ['gateway' => 'bkash'];
        $first = $this->withHeader('Idempotency-Key', 'portal-intent-1')
            ->postJson(route('customer.portal.payment-intent', [$tenant, $invoice]), $payload);
        $first->assertCreated()->assertJsonPath('status', 'pending');

        $second = $this->withHeader('Idempotency-Key', 'portal-intent-1')
            ->postJson(route('customer.portal.payment-intent', [$tenant, $invoice]), $payload);
        $second->assertCreated()->assertJsonPath('intent_id', $first->json('intent_id'));

        $this->assertDatabaseCount('customer_payment_intents', 1);
    }

    public function test_customer_portal_protected_responses_are_not_cacheable(): void
    {
        $tenant = $this->createTenant('cache-safe-isp');
        $customer = $this->createCustomer($tenant, 'cache-customer', '01900000000', 'pppoe');
        $account = TenantCustomerAccount::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'login_identifier' => '01900000000',
            'password' => Hash::make('portal-secret'),
            'status' => 'active',
        ]);

        $this->actingAs($account, 'customer');
        $response = $this->get(route('customer.portal.dashboard', $tenant));

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
    }

    private function createTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => strtoupper($slug),
            'company_name' => strtoupper($slug) . ' Communications',
            'slug' => $slug,
            'status' => 'active',
        ]);
    }

    private function createCustomer(Tenant $tenant, string $username, string $phone, string $password): TenantCustomer
    {
        return TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => strtoupper($username),
            'name' => 'Portal Customer',
            'username' => $username,
            'password' => $password,
            'phone' => $phone,
            'status' => 'active',
        ]);
    }
}
