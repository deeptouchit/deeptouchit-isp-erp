<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantAutomationSetting;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Services\Billing\IspCustomerAutomationService;
use App\Services\Network\RadiusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAutomationProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_generation_uses_tenant_date_rate_and_cycle_idempotency(): void
    {
        Carbon::setTestNow('2026-11-18 19:30:00 UTC');
        $tenant = Tenant::create([
            'name' => 'Timezone ISP',
            'company_name' => 'Timezone ISP',
            'slug' => 'timezone-automation-isp',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
        ]);
        $settings = TenantAutomationSetting::create([
            'tenant_id' => $tenant->id,
            'billing_generation_day' => 3,
        ]);
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => 'AUTO1001',
            'name' => 'Automation Customer',
            'username' => 'automation-customer',
            'password' => 'password',
            'phone' => '01700000001',
            'monthly_bill' => 350,
            'due_amount' => 0,
            'expiry_date' => '2026-11-21',
            'status' => 'active',
        ]);

        $this->mock(RadiusService::class)
            ->shouldReceive('syncCustomerSubscriber')
            ->once()
            ->andReturn(['success' => true]);
        $service = app(IspCustomerAutomationService::class);

        $first = $service->generateInvoicesForTenant($tenant, $settings, 'test');
        $second = $service->generateInvoicesForTenant($tenant, $settings, 'test-replay');

        $this->assertSame(1, $first['invoices_generated']);
        $this->assertSame(0, $second['invoices_generated']);
        $this->assertDatabaseCount('tenant_customer_invoices', 1);
        $invoice = TenantCustomerInvoice::sole();
        $this->assertSame('expiry:2026-11-21', $invoice->cycle_key);
        $this->assertSame(350.0, (float) $invoice->amount);
        $this->assertSame(350.0, (float) $customer->fresh()->due_amount);

        Carbon::setTestNow();
    }

    public function test_partial_automation_settlement_does_not_extend_or_reactivate(): void
    {
        Carbon::setTestNow('2026-11-18 12:00:00 Asia/Dhaka');
        $tenant = Tenant::create([
            'name' => 'Settlement ISP',
            'company_name' => 'Settlement ISP',
            'slug' => 'settlement-automation-isp',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
        ]);
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => 'AUTO2001',
            'name' => 'Partial Automation Customer',
            'username' => 'partial-automation',
            'password' => 'password',
            'phone' => '01700000002',
            'monthly_bill' => 500,
            'due_amount' => 500,
            'expiry_date' => '2026-11-10',
            'status' => 'suspended',
        ]);
        $invoice = TenantCustomerInvoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-AUTO-PARTIAL',
            'billing_month' => '2026-11',
            'cycle_key' => 'expiry:2026-11-10',
            'amount' => 500,
            'total_payable' => 500,
            'due_amount' => 500,
            'due_date' => '2026-11-10',
            'status' => 'overdue',
        ]);

        $result = app(IspCustomerAutomationService::class)
            ->settleAndReactivate($customer, 100, 'cash');

        $this->assertSame('suspended', $result['status']);
        $this->assertSame('2026-11-10', $result['new_expiry_date']);
        $this->assertSame(400.0, (float) $customer->fresh()->due_amount);
        $this->assertSame(400.0, (float) $invoice->fresh()->due_amount);
        $this->assertSame('partially_paid', $invoice->fresh()->status);

        Carbon::setTestNow();
    }
}
