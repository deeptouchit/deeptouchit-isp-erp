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
        if (!Schema::hasTable('tenant_reseller_bandwidth')) {
            Schema::create('tenant_reseller_bandwidth', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->onDelete('cascade');
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->onDelete('set null');
                $table->string('interface_name', 100)->nullable();
                $table->integer('vlan_id')->nullable();
                $table->decimal('global_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('bdix_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('cdn_bandwidth_mbps', 10, 2)->default(0.00);
                $table->decimal('total_bandwidth_mbps', 10, 2)->default(0.00);
                $table->enum('allocation_type', ['DEDICATED_CIR', 'BURSTABLE_MIR', 'SHARED_POOL'])->default('DEDICATED_CIR');
                $table->decimal('rate_per_mbps', 10, 2)->default(0.00);
                $table->decimal('monthly_bill_amount', 12, 2)->default(0.00);
                $table->string('mikrotik_queue_name', 100)->nullable();
                $table->decimal('current_usage_mbps', 10, 2)->default(0.00);
                $table->decimal('peak_usage_mbps', 10, 2)->default(0.00);
                $table->enum('status', ['ACTIVE', 'THROTTLED', 'SUSPENDED'])->default('ACTIVE');
                $table->string('notes', 255)->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'reseller_id']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'router_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_bandwidth');
    }
};
