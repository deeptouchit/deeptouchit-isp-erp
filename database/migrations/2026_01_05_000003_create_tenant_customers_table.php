<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_customers')) {
            Schema::create('tenant_customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->nullOnDelete();
                $table->foreignId('coverage_zone_id')->nullable()->constrained('tenant_coverage_zones')->nullOnDelete();
                $table->foreignId('internet_package_id')->nullable()->constrained('tenant_internet_packages')->nullOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->foreignId('olt_id')->nullable()->constrained('tenant_olts')->nullOnDelete();
                $table->foreignId('onu_id')->nullable()->constrained('tenant_onus')->nullOnDelete();
                $table->string('customer_id')->index(); // SO1001, etc.
                $table->string('name');
                $table->string('username')->unique();
                $table->string('password');
                $table->enum('connection_type', ['pppoe', 'static_ip', 'hotspot'])->default('pppoe');
                $table->string('ip_address')->nullable();
                $table->string('mac_address')->nullable();
                $table->string('phone');
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->enum('billing_type', ['prepaid', 'postpaid'])->default('prepaid');
                $table->decimal('monthly_bill', 10, 2)->default(0.00);
                $table->decimal('due_amount', 10, 2)->default(0.00);
                $table->decimal('advance_amount', 10, 2)->default(0.00);
                $table->date('expiry_date')->nullable();
                $table->enum('status', ['active', 'expired', 'suspended', 'pending'])->default('active');
                $table->enum('mikrotik_status', ['synced', 'error', 'pending'])->default('pending');
                $table->boolean('auto_disconnect')->default(true);
                $table->timestamp('last_sync_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'reseller_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_customers');
    }
};
