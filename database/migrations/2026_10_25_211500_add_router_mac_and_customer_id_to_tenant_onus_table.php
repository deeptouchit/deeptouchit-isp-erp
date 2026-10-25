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
        Schema::table('tenant_onus', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_onus', 'router_mac')) {
                $table->string('router_mac', 50)->nullable()->index()->after('mac_address');
            }
            if (!Schema::hasColumn('tenant_onus', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('tenant_id')->constrained('tenant_customers')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_onus', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_onus', 'customer_id')) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('tenant_onus', 'router_mac')) {
                $table->dropIndex(['router_mac']);
                $table->dropColumn('router_mac');
            }
        });
    }
};
