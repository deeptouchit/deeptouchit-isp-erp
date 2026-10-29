<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_upstream_providers')) {
            Schema::create('tenant_upstream_providers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('phone');
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_upstream_links')) {
            Schema::create('tenant_upstream_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('link_name');
                $table->string('circuit_id')->nullable();
                $table->decimal('bandwidth_mbps', 10, 2)->default(0.00);
                $table->string('interface_name')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('gateway_ip')->nullable();
                $table->string('vlan_id')->nullable();
                $table->enum('type', ['primary', 'backup', 'secondary'])->default('primary');
                $table->enum('status', ['up', 'down', 'testing'])->default('up');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_upstream_invoices')) {
            Schema::create('tenant_upstream_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
                $table->string('invoice_no')->unique();
                $table->string('billing_month', 20); // 2026-10
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->decimal('vat', 12, 2)->default(0.00);
                $table->decimal('discount', 12, 2)->default(0.00);
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->decimal('paid_amount', 12, 2)->default(0.00);
                $table->decimal('due_amount', 12, 2)->default(0.00);
                $table->enum('status', ['unpaid', 'paid', 'partial'])->default('unpaid');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_upstream_payments')) {
            Schema::create('tenant_upstream_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('tenant_upstream_invoices')->cascadeOnDelete();
                $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
                $table->string('payment_method')->default('bank_transfer');
                $table->decimal('amount', 12, 2);
                $table->date('payment_date');
                $table->string('reference_no')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_upstream_payments');
        Schema::dropIfExists('tenant_upstream_invoices');
        Schema::dropIfExists('tenant_upstream_links');
        Schema::dropIfExists('tenant_upstream_providers');
    }
};
