<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantReseller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBulkPaymentProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_payment_rejects_mixed_reseller_customers_without_mutation(): void
    {
        [$tenant, $user] = $this->tenantAndAdmin('mixed');
        $resellerA = $this->reseller($tenant, 'A');
        $resellerB = $this->reseller($tenant, 'B');
        $customerA = $this->customer($tenant, $resellerA, 'MX1001', 100);
        $customerB = $this->customer($tenant, $resellerB, 'MX1002', 100);

        $this->actingAs($user)->postJson(route('tenant.customers.bulk-payments.process'), [
            'customer_ids' => [$customerA->id, $customerB->id],
            'payment_method' => 'cash',
            'billing_month' => 'November 2026',
            'request_key' => 'mixed-reseller-batch',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('tenant_customer_payments', 0);
        $this->assertSame(100.0, (float) $customerA->fresh()->due_amount);
        $this->assertSame(100.0, (float) $customerB->fresh()->due_amount);
    }

    public function test_bulk_wallet_payment_is_atomic_skips_zero_value_and_is_replay_safe(): void
    {
        Carbon::setTestNow('2026-11-18 12:00:00');
        [$tenant, $user] = $this->tenantAndAdmin('wallet');
        $reseller = $this->reseller($tenant, 'Wallet', 10, 1000);

        $first = $this->customer($tenant, $reseller, 'WL1001', 500);
        $second = $this->customer($tenant, $reseller, 'WL1002', 500);
        $firstInvoice = $this->invoice($tenant, $reseller, $first, 'INV-WL-1001');
        $secondInvoice = $this->invoice($tenant, $reseller, $second, 'INV-WL-1002');
        $zeroValue = $this->customer($tenant, $reseller, 'WL-ZERO', 0);

        $payload = [
            'customer_ids' => [$first->id, $second->id, $zeroValue->id],
            'payment_method' => 'reseller_wallet',
            'billing_month' => 'November 2026',
            'extend_validity' => true,
            'reactivate_line' => true,
            'reseller_id' => $reseller->id,
            'request_key' => 'wallet-bulk-batch-001',
        ];

        $this->actingAs($user)->postJson(route('tenant.customers.bulk-payments.process'), $payload)
            ->assertOk()
            ->assertJsonPath('processed_count', 2)
            ->assertJsonPath('admin_share', '900.00');

        $this->assertSame(100.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 1);
        $this->assertDatabaseHas('tenant_reseller_wallet_transactions', [
            'amount' => 900,
            'reference_no' => 'wallet-bulk-batch-001',
        ]);
        $this->assertDatabaseCount('tenant_customer_payments', 2);
        $this->assertDatabaseCount('tenant_customer_payment_allocations', 2);

        foreach ([[$first, $firstInvoice], [$second, $secondInvoice]] as [$customer, $invoice]) {
            $this->assertSame(0.0, (float) $customer->fresh()->due_amount);
            $this->assertSame('active', $customer->fresh()->status);
            $this->assertSame('paid', $invoice->fresh()->status);
        }
        $this->assertSame(0.0, (float) $zeroValue->fresh()->due_amount);
        $this->assertSame('suspended', $zeroValue->fresh()->status);
        $this->assertSame('2026-11-10', $zeroValue->fresh()->expiry_date?->toDateString());

        $this->actingAs($user)->postJson(route('tenant.customers.bulk-payments.process'), $payload)
            ->assertOk()->assertJsonPath('already_processed', true);
        $this->assertSame(100.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 1);
        $this->assertDatabaseCount('tenant_customer_payments', 2);

        Carbon::setTestNow();
    }

    private function tenantAndAdmin(string $key): array
    {
        $tenant = Tenant::create([
            'name' => ucfirst($key) . ' ISP',
            'company_name' => ucfirst($key) . ' ISP',
            'slug' => $key . '-bulk-isp',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($key) . ' Admin',
            'email' => $key . '-bulk-admin@example.test',
            'password' => bcrypt('test-password'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);

        return [$tenant, $user];
    }

    private function reseller(Tenant $tenant, string $key, float $commission = 0, float $wallet = 0): TenantReseller
    {
        return TenantReseller::create([
            'tenant_id' => $tenant->id,
            'name' => $key . ' Reseller',
            'mobile' => '0171' . str_pad((string) $tenant->id, 7, '0'),
            'commission_rate' => $commission,
            'wallet_balance' => $wallet,
        ]);
    }

    private function customer(Tenant $tenant, TenantReseller $reseller, string $code, float $due): TenantCustomer
    {
        return TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $reseller->id,
            'customer_id' => $code,
            'name' => $code . ' Customer',
            'username' => strtolower($code),
            'password' => 'password',
            'phone' => '018' . str_pad((string) $reseller->id, 8, '0'),
            'monthly_bill' => $due,
            'due_amount' => $due,
            'expiry_date' => '2026-11-10',
            'status' => 'suspended',
        ]);
    }

    private function invoice(Tenant $tenant, TenantReseller $reseller, TenantCustomer $customer, string $number): TenantCustomerInvoice
    {
        return TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $reseller->id,
            'customer_id' => $customer->id,
            'invoice_no' => $number,
            'billing_month' => '2026-11',
            'amount' => 500,
            'total_payable' => 500,
            'due_amount' => 500,
            'due_date' => '2026-11-10',
            'status' => 'overdue',
        ]);
    }
}
