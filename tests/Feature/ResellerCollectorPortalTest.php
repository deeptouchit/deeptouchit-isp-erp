<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\TenantResellerWalletTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResellerCollectorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_menu_routes_and_customer_data_are_scoped(): void
    {
        [$collector, $own, $teammate, $foreign] = $this->accounts();
        $this->actingAs($collector)->withSession(['locale' => 'en']);
        $this->get(route('reseller.dashboard'))->assertOk()->assertSee('Assigned Customers')->assertSee('My Collections')
            ->assertSee($own->name)->assertDontSee($teammate->name)->assertDontSee($foreign->name)
            ->assertDontSee(route('reseller.recharge.index'), false);
        $this->get(route('reseller.customers.index', ['status' => 'due']))->assertOk()->assertSee($own->name)
            ->assertDontSee($teammate->name)->assertDontSee('router-password');
        $this->getJson(route('reseller.customers.show', $own))->assertOk()->assertJsonMissingPath('customer.password');
        $this->getJson(route('reseller.customers.show', $teammate))->assertNotFound();
        $this->getJson(route('reseller.customers.show', $foreign))->assertNotFound();
        $this->get(route('reseller.customers.show', $own))->assertOk()->assertSee('Collect Cash')->assertDontSee('router-password');
        $this->get(route('reseller.customers.print'))->assertOk()->assertSee($own->name)->assertDontSee($foreign->name);

        $csv = $this->get(route('reseller.customers.export', ['preset' => 'technical']))->assertOk()->streamedContent();
        $this->assertStringContainsString($own->name, $csv);
        $this->assertStringNotContainsString($teammate->name, $csv);
        $this->assertStringNotContainsString('router-password', $csv);

        $this->getJson(route('reseller.customers.create'))->assertForbidden();
        $this->putJson(route('reseller.customers.update', $own))->assertForbidden();
        $this->postJson(route('reseller.customers.update-pppoe', $own))->assertForbidden();
        $this->postJson(route('reseller.customers.bulk-payments.process'))->assertForbidden();
        $this->getJson(route('reseller.bandwidth.traffic'))->assertForbidden();

        $otherTicket = SupportTicket::create([
            'tenant_id' => $collector->tenant_id, 'reseller_id' => $collector->reseller_id,
            'user_id' => $teammate->assigned_collector_id, 'ticket_number' => 'COL-TEST-001',
            'subject' => 'Other collector ticket', 'department' => 'general', 'priority' => 'medium', 'status' => 'open',
        ]);
        $this->get(route('reseller.tickets.show', $otherTicket))->assertNotFound();
    }

    public function test_cash_payment_retries_reuse_receipt_and_apply_financial_changes_once(): void
    {
        [$collector, $customer, $teammate] = $this->accounts();
        $invoice = TenantCustomerInvoice::create([
            'tenant_id' => $collector->tenant_id, 'customer_id' => $customer->id, 'invoice_no' => 'INV-COL-01',
            'billing_month' => '2026-11', 'total_payable' => 100, 'total_amount' => 100,
            'due_amount' => 100, 'paid_amount' => 0, 'status' => 'unpaid', 'due_date' => today(),
        ]);

        $this->actingAs($collector);
        $input = ['request_key' => (string) Str::uuid(), 'amount' => '60.00', 'payment_method' => 'cash'];
        $this->postJson(route('reseller.customers.pay-bill', $teammate), $input)->assertNotFound();
        $url = route('reseller.customers.pay-bill', $customer);
        $this->postJson($url, ['payment_method' => 'bank_transfer'] + $input)->assertUnprocessable();
        $receipt = $this->postJson($url, $input)->assertOk()->json('invoice_no');
        $receiptUrl = $this->postJson($url, $input)->assertOk()->assertJsonPath('invoice_no', $receipt)->json('receipt_url');
        $this->postJson($url, ['amount' => '50.00'] + $input)->assertUnprocessable();
        $this->postJson($url, ['request_key' => (string) Str::uuid(), 'amount' => '41.00'] + $input)->assertUnprocessable();

        $this->assertDatabaseCount('tenant_customer_payments', 1);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 1);
        $this->assertEquals(952, $collector->reseller()->firstOrFail()->wallet_balance);
        $this->assertEquals(40, $customer->refresh()->due_amount);
        $this->assertEquals(60, $invoice->refresh()->paid_amount);
        $this->assertSame('partial', $invoice->status);

        $this->getJson($receiptUrl)->assertOk()->assertJsonPath('receipt_no', $receipt);
        $this->get(route('reseller.collections.index'))->assertOk()->assertSee($receipt);
        $this->actingAs(User::findOrFail($teammate->assigned_collector_id))->getJson($receiptUrl)->assertNotFound();
    }

    public function test_reseller_admin_can_reassign_only_to_an_active_collector_of_the_same_reseller(): void
    {
        [$collector, $own, $teammate, $foreign] = $this->accounts();
        $admin = User::factory()->create([
            'tenant_id' => $collector->tenant_id, 'reseller_id' => $collector->reseller_id,
            'role' => 'reseller_admin', 'status' => 'active',
        ]);
        $route = route('reseller.customers.assign-collector', $own);
        $this->actingAs($collector)->postJson($route, ['assigned_collector_id' => $teammate->assigned_collector_id])->assertForbidden();
        $this->actingAs($admin)->postJson($route, ['assigned_collector_id' => $foreign->assigned_collector_id])->assertUnprocessable();
        $this->postJson($route, ['assigned_collector_id' => $teammate->assigned_collector_id])->assertOk();
        $this->assertEquals($teammate->assigned_collector_id, $own->refresh()->assigned_collector_id);
        $this->actingAs($collector)->getJson(route('reseller.customers.show', $own))->assertNotFound();
        $this->actingAs(User::findOrFail($teammate->assigned_collector_id))->getJson(route('reseller.customers.show', $own))->assertOk();
    }

    private function accounts(): array
    {
        $tenant = Tenant::create(['name' => 'Collector ISP', 'company_name' => 'Collector ISP', 'slug' => 'collector-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Own Reseller', 'mobile' => '01710000051', 'wallet_balance' => 1000, 'commission_rate' => 20]);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Reseller', 'mobile' => '01710000052']);
        $staff = fn ($partner) => User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $partner->id, 'role' => 'reseller_collector', 'status' => 'active',
        ]);
        $collector = $staff($reseller);
        $teammate = $staff($reseller);
        $otherCollector = $staff($other);
        $customer = fn ($actor, $code) => TenantCustomer::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $actor->reseller_id, 'assigned_collector_id' => $actor->id,
            'customer_id' => $code, 'name' => $code . ' Subscriber', 'username' => strtolower($code), 'password' => 'router-password',
            'phone' => '01810000051', 'status' => 'active', 'due_amount' => 100,
        ]);

        return [$collector, $customer($collector, 'OWN'), $customer($teammate, 'TEAM'), $customer($otherCollector, 'FOREIGN')];
    }
}
