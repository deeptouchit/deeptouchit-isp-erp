<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenant_customers', 'assigned_collector_id')) {
            Schema::table('tenant_customers', function (Blueprint $table) {
                $table->foreignId('assigned_collector_id')->nullable()->constrained('users')->nullOnDelete();
                $table->index(['tenant_id', 'reseller_id', 'assigned_collector_id'], 'tenant_customers_collector_scope_idx');
            });
        }

        if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            Schema::table('tenant_customer_payments', function (Blueprint $table) {
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->index(['tenant_id', 'reseller_id', 'collected_by'], 'tenant_customer_payments_collector_scope_idx');
            });
        }
    }

    public function down(): void
    {
        // These columns can already exist and contain collector assignments and payment history.
    }
};
