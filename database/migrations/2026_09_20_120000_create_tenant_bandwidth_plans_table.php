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
        if (!Schema::hasTable('tenant_bandwidth_plans')) {
            Schema::create('tenant_bandwidth_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name', 150);
                $table->string('code', 50)->nullable();
                $table->string('pricing_mode', 50)->default('COMPONENT_BASED'); // COMPONENT_BASED, FLAT_PER_MBPS, FIXED_BUNDLE
                $table->enum('allocation_type', ['DEDICATED_CIR', 'BURSTABLE_MIR', 'SHARED_POOL'])->default('DEDICATED_CIR');
                $table->decimal('global_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('bdix_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('cdn_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('flat_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('bundle_global_mbps', 10, 2)->default(0.00);
                $table->decimal('bundle_bdix_mbps', 10, 2)->default(0.00);
                $table->decimal('bundle_cdn_mbps', 10, 2)->default(0.00);
                $table->decimal('bundle_price', 12, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'bandwidth_plan_id')) {
                    $table->foreignId('bandwidth_plan_id')->nullable()->after('router_id')->constrained('tenant_bandwidth_plans')->onDelete('set null');
                }
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'global_rate_per_mbps')) {
                    $table->decimal('global_rate_per_mbps', 10, 2)->default(0.00)->after('rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'bdix_rate_per_mbps')) {
                    $table->decimal('bdix_rate_per_mbps', 10, 2)->default(0.00)->after('global_rate_per_mbps');
                }
                if (!Schema::hasColumn('tenant_reseller_bandwidth', 'cdn_rate_per_mbps')) {
                    $table->decimal('cdn_rate_per_mbps', 10, 2)->default(0.00)->after('bdix_rate_per_mbps');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::table('tenant_reseller_bandwidth', function (Blueprint $table) {
                if (Schema::hasColumn('tenant_reseller_bandwidth', 'bandwidth_plan_id')) {
                    $table->dropForeign(['bandwidth_plan_id']);
                    $table->dropColumn('bandwidth_plan_id');
                }
                if (Schema::hasColumn('tenant_reseller_bandwidth', 'global_rate_per_mbps')) {
                    $table->dropColumn('global_rate_per_mbps');
                }
                if (Schema::hasColumn('tenant_reseller_bandwidth', 'bdix_rate_per_mbps')) {
                    $table->dropColumn('bdix_rate_per_mbps');
                }
                if (Schema::hasColumn('tenant_reseller_bandwidth', 'cdn_rate_per_mbps')) {
                    $table->dropColumn('cdn_rate_per_mbps');
                }
            });
        }

        Schema::dropIfExists('tenant_bandwidth_plans');
    }
};
