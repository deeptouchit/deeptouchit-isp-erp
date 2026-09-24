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
        Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_reseller_bandwidth', 'ggc_bandwidth_mbps')) {
                $table->decimal('ggc_bandwidth_mbps', 10, 2)->default(0.00)->after('cdn_bandwidth_mbps');
            }
            if (!Schema::hasColumn('tenant_reseller_bandwidth', 'fna_bandwidth_mbps')) {
                $table->decimal('fna_bandwidth_mbps', 10, 2)->default(0.00)->after('ggc_bandwidth_mbps');
            }
            if (!Schema::hasColumn('tenant_reseller_bandwidth', 'other_bandwidth_mbps')) {
                $table->decimal('other_bandwidth_mbps', 10, 2)->default(0.00)->after('fna_bandwidth_mbps');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
            if (Schema::hasColumn('tenant_reseller_bandwidth', 'ggc_bandwidth_mbps')) {
                $table->dropColumn('ggc_bandwidth_mbps');
            }
            if (Schema::hasColumn('tenant_reseller_bandwidth', 'fna_bandwidth_mbps')) {
                $table->dropColumn('fna_bandwidth_mbps');
            }
            if (Schema::hasColumn('tenant_reseller_bandwidth', 'other_bandwidth_mbps')) {
                $table->dropColumn('other_bandwidth_mbps');
            }
        });
    }
};
