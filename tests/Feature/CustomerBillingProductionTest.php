<?php

namespace Tests\Feature;

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
}
