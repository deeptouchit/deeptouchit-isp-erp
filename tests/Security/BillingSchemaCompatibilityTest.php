<?php

namespace Tests\Security;

use App\Models\SaasInvoice;
use App\Models\Tenant;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BillingSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_payment_preserves_supported_live_status_and_balance(): void
    {
        $tenant = Tenant::create(['name' => 'Audit', 'company_name' => 'Audit', 'slug' => 'audit', 'status' => 'active']);
        $invoice = SaasInvoice::create(['tenant_id' => $tenant->id, 'invoice_no' => 'AUDIT-PARTIAL', 'amount' => 100, 'due_amount' => 100, 'status' => 'unpaid']);
        $invoice = (new InvoiceService)->recordPayment($invoice, 25, 'audit', 'audit-partial-reference');
        $this->assertSame('unpaid', $invoice->status);
        $this->assertEquals(25, $invoice->paid_amount);
        $this->assertEquals(75, $invoice->due_amount);
    }

    public function test_verified_create_migrations_do_not_modify_existing_tables(): void
    {
        $tenant = Tenant::create(['name' => 'Audit', 'company_name' => 'Audit', 'slug' => 'audit', 'status' => 'active']);
        $invoice = SaasInvoice::create(['tenant_id' => $tenant->id, 'invoice_no' => 'AUDIT-KEEP', 'amount' => 100, 'due_amount' => 100, 'status' => 'unpaid']);
        $before = $invoice->fresh()->getRawOriginal();
        $columns = Schema::getColumns('saas_invoices');
        foreach ([
            '0001_01_01_000004_create_settings_table.php',
            '2026_01_01_000003_create_tenant_subscriptions_table.php',
            '2026_01_01_000004_create_saas_invoices_table.php',
            '2026_01_01_000006_create_payment_transactions_and_roles_tables.php',
            '2026_01_02_000001_create_tenant_routers_table.php',
            '2026_01_09_000003_create_audit_and_activity_logs_tables.php',
        ] as $filename) {
            $migration = require database_path('migrations/'.$filename);
            $migration->up();
        }
        $this->assertSame($before, $invoice->fresh()->getRawOriginal());
        $this->assertSame($columns, Schema::getColumns('saas_invoices'));
    }
}
