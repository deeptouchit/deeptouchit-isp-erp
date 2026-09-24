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
        if (Schema::hasTable('tenant_bandwidth_plans')) {
            Schema::table('tenant_bandwidth_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('tenant_bandwidth_plans', 'min_bandwidth_mbps')) {
                    $table->decimal('min_bandwidth_mbps', 10, 2)->default(0.00)->after('code');
                }
                if (!Schema::hasColumn('tenant_bandwidth_plans', 'max_bandwidth_mbps')) {
                    $table->decimal('max_bandwidth_mbps', 10, 2)->nullable()->after('min_bandwidth_mbps');
                }
                if (!Schema::hasColumn('tenant_bandwidth_plans', 'ggc_rate_per_mbps')) {
                    $table->decimal('ggc_rate_per_mbps', 10, 2)->default(0.00)->after('cdn_rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_bandwidth_plans', 'fna_rate_per_mbps')) {
                    $table->decimal('fna_rate_per_mbps', 10, 2)->default(0.00)->after('ggc_rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_bandwidth_plans', 'others_rate_per_mbps')) {
                    $table->decimal('others_rate_per_mbps', 10, 2)->default(0.00)->after('fna_rate_per_mbps');
                }
            });
        }

        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'ggc_rate_per_mbps')) {
                    $table->decimal('ggc_rate_per_mbps', 10, 2)->default(0.00)->after('cdn_rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'fna_rate_per_mbps')) {
                    $table->decimal('fna_rate_per_mbps', 10, 2)->default(0.00)->after('ggc_rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'others_rate_per_mbps')) {
                    $table->decimal('others_rate_per_mbps', 10, 2)->default(0.00)->after('fna_rate_per_mbps');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_bandwidth_plans')) {
            Schema::table('tenant_bandwidth_plans', function (Blueprint $table) {
                $columns = ['min_bandwidth_mbps', 'max_bandwidth_mbps', 'ggc_rate_per_mbps', 'fna_rate_per_mbps', 'others_rate_per_mbps'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('tenant_bandwidth_plans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
                $columns = ['ggc_rate_per_mbps', 'fna_rate_per_mbps', 'others_rate_per_mbps'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('tenant_reseller_bandwidth', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
