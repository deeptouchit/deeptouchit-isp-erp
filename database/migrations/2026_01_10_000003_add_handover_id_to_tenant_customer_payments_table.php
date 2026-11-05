<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_customer_payments') && !Schema::hasColumn('tenant_customer_payments', 'handover_id')) {
            Schema::table('tenant_customer_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('handover_id')->nullable()->after('invoice_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_customer_payments') && Schema::hasColumn('tenant_customer_payments', 'handover_id')) {
            Schema::table('tenant_customer_payments', function (Blueprint $table) {
                $table->dropColumn('handover_id');
            });
        }
    }
};

