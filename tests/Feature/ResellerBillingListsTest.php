<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerInvoice;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerBillingListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_and_collection_outputs_keep_the_same_scope(): void
    {
        if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            Schema::table('tenant_customer_payments', fn (Blueprint $table) => $table->unsignedBigInteger('reseller_id')->nullable());
        }

        $tenant = Tenant::create(['name' => 'Billing ISP', 'company_name' => 'Billing ISP', 'slug' => 'billing-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Billing Reseller', 'mobile' => '01710000001']);
        $admin = $this->billingUser($reseller, 'reseller_admin');
        $collector = $this->billingUser($reseller, 'reseller_collector');
        $otherCollector = $this->billingUser($reseller, 'reseller_collector');
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => 'BILL-1',
            'name' => 'Billing Customer', 'username' => 'billing-customer', 'password' => 'test', 'phone' => '01810000001',
        ]);

        foreach (['partially_paid' => 'INV-PARTIAL', 'paid' => 'INV-PAID'] as $status => $invoiceNo) {
            TenantCustomerInvoice::create([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $customer->id,
                'invoice_no' => $invoiceNo, 'billing_month' => '2026-11', 'status' => $status,
                'issue_date' => '2026-11-01', 'due_date' => '2026-11-10', 'total_payable' => 100,
                'paid_amount' => $status === 'paid' ? 100 : 40, 'due_amount' => $status === 'paid' ? 0 : 60,
            ]);
        }

        $this->actingAs($admin);
        $filters = ['status' => 'due', 'search' => 'INV', 'month' => '2026-11'];
        foreach (['index', 'print-report'] as $route) {
            $this->get(route('reseller.customer-invoices.' . $route, $filters))->assertOk()
                ->assertViewHas('invoices', fn ($invoices) => $invoices->pluck('invoice_no')->all() === ['INV-PARTIAL']);
        }
        $invoiceCsv = $this->get(route('reseller.customer-invoices.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('INV-PARTIAL', $invoiceCsv);
        $this->assertStringNotContainsString('INV-PAID', $invoiceCsv);
        $this->get(route('reseller.customer-invoices.index', ['status' => 'paid']))->assertOk()
            ->assertViewHas('invoices', fn ($invoices) => $invoices->pluck('invoice_no')->all() === ['INV-PAID']);

        foreach ([$collector->id => 'OWN-RECEIPT', $otherCollector->id => 'OTHER-RECEIPT'] as $collectorId => $receipt) {
            DB::table('tenant_customer_payments')->insert([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $customer->id,
                'invoice_no' => $receipt, 'collected_by' => $collectorId, 'amount' => 40,
                'status' => 'completed', 'paid_at' => now(),
            ]);
        }

        $this->actingAs($collector);
        $filters = ['status' => 'paid', 'period' => 'all'];
        $this->get(route('reseller.collections.index', $filters))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('invoice_no')->all() === ['OWN-RECEIPT']);
        $this->get(route('reseller.collections.print', $filters))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('invoice_no')->all() === ['OWN-RECEIPT']);
        $paymentCsv = $this->get(route('reseller.collections.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('OWN-RECEIPT', $paymentCsv);
        $this->assertStringNotContainsString('OTHER-RECEIPT', $paymentCsv);
        $otherPayment = TenantCustomerPayment::where('invoice_no', 'OTHER-RECEIPT')->firstOrFail();
        $this->getJson(route('reseller.collections.receipt', $otherPayment))->assertNotFound();
        $this->postJson(route('reseller.collections.approve', $otherPayment))->assertForbidden();
    }

    private function billingUser(TenantReseller $reseller, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $reseller->tenant_id, 'reseller_id' => $reseller->id, 'role' => $role, 'status' => 'active',
        ]);
    }
}
