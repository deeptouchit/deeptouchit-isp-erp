<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\User;
use App\Services\Billing\CustomerPaymentPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerBillingProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_billing_schema_supports_atomic_payment_workflow(): void
    {
        $this->assertTrue(Schema::hasColumns('tenant_customer_payments', [
            'tenant_id',
            'customer_id',
            'invoice_id',
            'invoice_no',
            'billing_month',
            'amount',
            'discount',
            'payment_method',
            'transaction_id',
            'request_key',
            'collected_by',
            'status',
            'paid_at',
        ]));

        $this->assertTrue(Schema::hasColumns('tenant_customer_invoices', [
            'tenant_id',
            'customer_id',
            'invoice_no',
            'billing_month',
            'amount',
            'vat_tax',
            'discount',
            'total_payable',
            'paid_amount',
            'due_amount',
            'issue_date',
            'due_date',
            'status',
            'paid_at',
        ]));

        $this->assertTrue(Schema::hasColumns('tenant_customer_payment_allocations', [
            'tenant_id',
            'payment_id',
            'invoice_id',
            'amount',
            'discount',
        ]));
    }

    public function test_partial_payment_does_not_extend_validity_or_reactivate_line(): void
    {
        $result = app(CustomerPaymentPolicy::class)->calculate(
            oldDue: 500,
            oldExpiry: Carbon::parse('2026-11-10'),
            monthlyBill: 500,
            mode: 'due',
            amountPaid: 100,
            discount: 0,
            months: 1,
            extendValidity: true,
            reactivateRequested: true,
            now: Carbon::parse('2026-11-18 12:00:00'),
        );

        $this->assertSame(400.0, $result['new_due']);
        $this->assertFalse($result['validity_extended']);
        $this->assertFalse($result['can_reactivate']);
        $this->assertSame('2026-11-10', $result['new_expiry']?->toDateString());
    }

    public function test_discount_can_settle_due_but_cannot_purchase_validity(): void
    {
        $result = app(CustomerPaymentPolicy::class)->calculate(
            oldDue: 500,
            oldExpiry: Carbon::parse('2026-11-10'),
            monthlyBill: 500,
            mode: 'due',
            amountPaid: 100,
            discount: 400,
            months: 1,
            extendValidity: true,
            reactivateRequested: true,
            now: Carbon::parse('2026-11-18 12:00:00'),
        );

        $this->assertSame(0.0, $result['new_due']);
        $this->assertFalse($result['validity_extended']);
        $this->assertFalse($result['can_reactivate']);
    }

    public function test_full_cash_payment_extends_validity_and_allows_reactivation(): void
    {
        $result = app(CustomerPaymentPolicy::class)->calculate(
            oldDue: 500,
            oldExpiry: Carbon::parse('2026-11-10'),
            monthlyBill: 500,
            mode: 'due',
            amountPaid: 500,
            discount: 0,
            months: 1,
            extendValidity: true,
            reactivateRequested: true,
            now: Carbon::parse('2026-11-18 12:00:00'),
        );

        $this->assertSame(0.0, $result['new_due']);
        $this->assertTrue($result['validity_extended']);
        $this->assertTrue($result['can_reactivate']);
        $this->assertSame('2026-12-18', $result['new_expiry']?->toDateString());
    }

    public function test_partial_payment_is_allocated_once_without_reactivation_or_expiry_extension(): void
    {
        Carbon::setTestNow('2026-11-18 12:00:00');

        $tenant = Tenant::create([
            'name' => 'Billing Test ISP',
            'company_name' => 'Billing Test ISP',
            'slug' => 'billing-test-isp',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Billing Admin',
            'email' => 'billing-admin@example.test',
            'password' => bcrypt('test-password'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => 'BT1001',
            'name' => 'Partial Payment Customer',
            'username' => 'partial-customer',
            'password' => 'pppoe-password',
            'phone' => '01700000000',
            'monthly_bill' => 500,
            'due_amount' => 500,
            'expiry_date' => '2026-11-10',
            'status' => 'suspended',
        ]);
        $invoice = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-202611-1001',
            'billing_month' => '2026-11',
            'amount' => 500,
            'total_payable' => 500,
            'paid_amount' => 0,
            'due_amount' => 500,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
        ]);

        $payload = [
            'payment_mode' => 'due',
            'amount' => 100,
            'discount' => 0,
            'months' => 1,
            'payment_method' => 'cash',
            'billing_month' => 'November 2026',
            'extend_validity' => true,
            'reactivate_line' => true,
            'request_key' => 'partial-payment-request-001',
        ];

        $response = $this->actingAs($user)->postJson(
            route('tenant.customers.pay-bill', $customer->id),
            $payload,
        );
        $response->assertOk()->assertJsonPath('success', true);

        $customer->refresh();
        $invoice->refresh();
        $this->assertSame(400.0, (float) $customer->due_amount);
        $this->assertSame('suspended', $customer->status);
        $this->assertSame('2026-11-10', $customer->expiry_date?->toDateString());
        $this->assertSame(100.0, (float) $invoice->paid_amount);
        $this->assertSame(400.0, (float) $invoice->due_amount);
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertDatabaseCount('tenant_customer_payments', 1);
        $this->assertDatabaseHas('tenant_customer_payment_allocations', [
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'discount' => 0,
        ]);

        $replay = $this->actingAs($user)->postJson(
            route('tenant.customers.pay-bill', $customer->id),
            $payload,
        );
        $replay->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('already_processed', true);
        $this->assertDatabaseCount('tenant_customer_payments', 1);
        $this->assertSame(400.0, (float) $customer->fresh()->due_amount);

        $conflictingReplay = $this->actingAs($user)->postJson(
            route('tenant.customers.pay-bill', $customer->id),
            [...$payload, 'amount' => 200],
        );
        $conflictingReplay->assertConflict()->assertJsonPath('success', false);
        $this->assertDatabaseCount('tenant_customer_payments', 1);
        $this->assertSame(400.0, (float) $customer->fresh()->due_amount);

        Carbon::setTestNow();
    }

    public function test_payment_is_allocated_to_oldest_invoices_first(): void
    {
        Carbon::setTestNow('2026-11-18 12:00:00');

        $tenant = Tenant::create([
            'name' => 'Allocation Test ISP',
            'company_name' => 'Allocation Test ISP',
            'slug' => 'allocation-test-isp',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Allocation Admin',
            'email' => 'allocation-admin@example.test',
            'password' => bcrypt('test-password'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => 'AT1001',
            'name' => 'Allocation Customer',
            'username' => 'allocation-customer',
            'password' => 'pppoe-password',
            'phone' => '01800000000',
            'monthly_bill' => 300,
            'due_amount' => 600,
            'expiry_date' => '2026-11-10',
            'status' => 'suspended',
        ]);

        $oldest = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-202610-1001',
            'billing_month' => '2026-10',
            'amount' => 300,
            'total_payable' => 300,
            'due_amount' => 300,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-10',
            'status' => 'overdue',
        ]);
        $newest = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-202611-1002',
            'billing_month' => '2026-11',
            'amount' => 300,
            'total_payable' => 300,
            'due_amount' => 300,
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)->postJson(route('tenant.customers.pay-bill', $customer->id), [
            'payment_mode' => 'due',
            'amount' => 450,
            'discount' => 0,
            'months' => 1,
            'payment_method' => 'cash',
            'billing_month' => 'November 2026',
            'extend_validity' => true,
            'reactivate_line' => true,
            'request_key' => 'allocation-payment-request-001',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('paid', $oldest->fresh()->status);
        $this->assertSame(0.0, (float) $oldest->fresh()->due_amount);
        $this->assertSame('partially_paid', $newest->fresh()->status);
        $this->assertSame(150.0, (float) $newest->fresh()->due_amount);
        $this->assertSame(150.0, (float) $customer->fresh()->due_amount);
        $this->assertSame('suspended', $customer->fresh()->status);
        $this->assertDatabaseCount('tenant_customer_payment_allocations', 2);

        Carbon::setTestNow();
    }
}
