<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->decimal('yearly_price', 10, 2)->default(0.00);
            $table->integer('customer_limit')->default(500);
            $table->integer('mikrotik_limit')->default(2);
            $table->integer('reseller_limit')->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'pending', 'cancelled'])->default('active');
            $table->date('subscription_expires_at')->nullable();
            $table->decimal('wallet_balance', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('isp_admin')->after('phone'); // owner, isp_admin, reseller, manager, collector, technician, customer
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->after('role');
            $table->string('avatar')->nullable()->after('status');
        });

        Schema::create('saas_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->string('invoice_no')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['paid', 'unpaid', 'cancelled'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('trx_id')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_invoices');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'phone', 'role', 'status', 'avatar']);
        });
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('saas_plans');
    }
};
