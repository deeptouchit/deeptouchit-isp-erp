<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_bandwidth_plans')) {
            Schema::create('tenant_bandwidth_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_rate_per_mbps', 10, 2)->default(0.00);
                $table->json('volume_slabs')->nullable();
                $table->json('category_rates')->nullable();
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::create('tenant_reseller_bandwidth', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->foreignId('bandwidth_plan_id')->nullable()->constrained('tenant_bandwidth_plans')->nullOnDelete();
                $table->string('interface_name', 100)->nullable();
                $table->string('vlan_id', 150)->nullable();
                $table->decimal('global_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('bdix_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('cdn_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('ggc_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('fna_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('other_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('total_bandwidth_mbps', 10, 2)->default(0.00);
                $table->enum('allocation_type', ['DEDICATED_CIR', 'BURSTABLE_MIR', 'SHARED_POOL'])->default('DEDICATED_CIR');
                $table->decimal('rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('global_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('bdix_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('cdn_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('ggc_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('fna_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('others_rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('monthly_bill_amount', 12, 2)->default(0.00);
                $table->string('mikrotik_queue_name', 100)->nullable();
                $table->decimal('current_usage_mbps', 10, 2)->default(0.00);
                $table->decimal('peak_usage_mbps', 10, 2)->default(0.00);
                $table->enum('status', ['ACTIVE', 'THROTTLED', 'SUSPENDED'])->default('ACTIVE');
                $table->string('notes')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'reseller_id']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_bandwidth');
        Schema::dropIfExists('tenant_bandwidth_plans');
    }
};
