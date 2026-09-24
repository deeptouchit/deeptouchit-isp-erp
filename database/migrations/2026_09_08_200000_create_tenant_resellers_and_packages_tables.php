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
        // 1. Create tenant_resellers table if not exists
        if (!Schema::hasTable('tenant_resellers')) {
            Schema::create('tenant_resellers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('code', 50);
                $table->string('prefix', 20)->nullable();
                $table->string('contact_person');
                $table->string('mobile', 50);
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                
                // Billing & Financial Settings
                $table->enum('billing_type', ['PREPAID_WALLET', 'POSTPAID_MONTHLY', 'BANDWIDTH_WHOLESALE'])->default('PREPAID_WALLET');
                $table->decimal('wallet_balance', 12, 2)->default(0.00);
                $table->decimal('credit_limit', 12, 2)->default(0.00);
                $table->decimal('commission_rate', 5, 2)->default(0.00);
                
                // Monthly Software / Panel Subscription
                $table->decimal('monthly_panel_charge', 10, 2)->default(0.00);
                $table->date('panel_expiry_date')->nullable();
                $table->enum('panel_billing_status', ['ACTIVE', 'EXPIRED', 'GRACE_PERIOD'])->default('ACTIVE');
                
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
                $table->index(['tenant_id', 'status']);
            });
        }

        // 2. Add wholesale & reseller pricing fields to tenant_internet_packages table
        if (Schema::hasTable('tenant_internet_packages')) {
            Schema::table('tenant_internet_packages', function (Blueprint $table) {
                if (!Schema::hasColumn('tenant_internet_packages', 'wholesale_price')) {
                    $table->decimal('wholesale_price', 10, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('tenant_internet_packages', 'min_retail_price')) {
                    $table->decimal('min_retail_price', 10, 2)->nullable()->after('wholesale_price');
                }
                if (!Schema::hasColumn('tenant_internet_packages', 'allow_resellers')) {
                    $table->boolean('allow_resellers')->default(true)->after('min_retail_price');
                }
            });
        }

        // 3. Create tenant_reseller_packages table for custom pricing overrides per reseller
        if (!Schema::hasTable('tenant_reseller_packages')) {
            Schema::create('tenant_reseller_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('reseller_id')->constrained('tenant_resellers')->onDelete('cascade');
                $table->foreignId('package_id')->constrained('tenant_internet_packages')->onDelete('cascade');
                
                // Custom Pricing Overrides
                $table->decimal('custom_wholesale_price', 10, 2)->nullable();
                $table->decimal('min_retail_price', 10, 2)->nullable();
                $table->decimal('custom_retail_price', 10, 2)->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'reseller_id', 'package_id'], 'reseller_pkg_unique');
                $table->index(['tenant_id', 'reseller_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_packages');
        
        if (Schema::hasTable('tenant_internet_packages')) {
            Schema::table('tenant_internet_packages', function (Blueprint $table) {
                $table->dropColumn(['wholesale_price', 'min_retail_price', 'allow_resellers']);
            });
        }

        Schema::dropIfExists('tenant_resellers');
    }
};
