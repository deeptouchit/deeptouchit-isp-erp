<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_reseller_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_reseller_invoices', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0.00)->after('type');
            }
            if (!Schema::hasColumn('tenant_reseller_invoices', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0.00)->after('amount');
            }
            if (!Schema::hasColumn('tenant_reseller_invoices', 'vat_tax')) {
                $table->decimal('vat_tax', 12, 2)->default(0.00)->after('discount');
            }
            if (!Schema::hasColumn('tenant_reseller_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('period_end');
            }
            if (!Schema::hasColumn('tenant_reseller_invoices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tenant_reseller_invoices', 'item_details')) {
                $table->json('item_details')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_reseller_invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['subtotal', 'discount', 'vat_tax', 'due_date', 'created_by', 'item_details']);
        });
    }
};
