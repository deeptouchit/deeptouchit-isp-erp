<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_customer_invoices')
            && !Schema::hasColumn('tenant_customer_invoices', 'cycle_key')) {
            Schema::table('tenant_customer_invoices', function (Blueprint $table): void {
                $table->string('cycle_key', 40)->nullable()->after('billing_month');
                $table->unique(
                    ['tenant_id', 'customer_id', 'cycle_key'],
                    'customer_invoice_cycle_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Kept intentionally non-destructive for financial audit history.
    }
};
