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
        Schema::table('tenant_customer_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
                $table->foreignId('reseller_id')->nullable()->after('tenant_id')->constrained('tenant_resellers')->onDelete('set null');
            }
            if (!Schema::hasColumn('tenant_customer_payments', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('customer_id')->constrained('tenant_customer_invoices')->onDelete('set null');
            }
            if (!Schema::hasColumn('tenant_customer_payments', 'sms_sent')) {
                $table->boolean('sms_sent')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('tenant_customer_payments', 'sms_sent_at')) {
                $table->timestamp('sms_sent_at')->nullable()->after('sms_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_customer_payments', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_customer_payments', 'sms_sent_at')) {
                $table->dropColumn('sms_sent_at');
            }
            if (Schema::hasColumn('tenant_customer_payments', 'sms_sent')) {
                $table->dropColumn('sms_sent');
            }
            if (Schema::hasColumn('tenant_customer_payments', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
                $table->dropForeign(['reseller_id']);
                $table->dropColumn('reseller_id');
            }
        });
    }
};
