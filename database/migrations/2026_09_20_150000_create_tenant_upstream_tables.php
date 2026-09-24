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
        // 1. Upstream Providers (IIG, ITC, NTTN, BDIX, Cache Carriers)
        Schema::create('tenant_upstream_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('carrier_type')->default('IIG'); // IIG, ITC, NTTN, BDIX, CACHE_PROVIDER
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('routing_no')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'carrier_type']);
            $table->index(['tenant_id', 'is_active']);
        });

        // 2. Upstream Bandwidth Capacity & Delivery Links
        Schema::create('tenant_upstream_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->string('link_name');
            $table->string('interface_port')->nullable();
            $table->string('circuit_id')->nullable();

            // Capacities in Mbps
            $table->decimal('global_mbps', 10, 2)->default(0);
            $table->decimal('bdix_mbps', 10, 2)->default(0);
            $table->decimal('cdn_mbps', 10, 2)->default(0);
            $table->decimal('ggc_mbps', 10, 2)->default(0);
            $table->decimal('fna_mbps', 10, 2)->default(0);
            $table->decimal('other_mbps', 10, 2)->default(0);
            $table->decimal('total_mbps', 10, 2)->default(0);

            // Unit Tariff per Mbps
            $table->decimal('global_rate', 10, 2)->default(0);
            $table->decimal('bdix_rate', 10, 2)->default(0);
            $table->decimal('cdn_rate', 10, 2)->default(0);
            $table->decimal('ggc_rate', 10, 2)->default(0);
            $table->decimal('fna_rate', 10, 2)->default(0);
            $table->decimal('other_rate', 10, 2)->default(0);

            // Transmission & Estimated Bill
            $table->decimal('monthly_transmission_cost', 12, 2)->default(0);
            $table->decimal('est_monthly_bill', 12, 2)->default(0);
            $table->string('status')->default('ACTIVE'); // ACTIVE, STANDBY, SUSPENDED
            $table->timestamps();

            $table->index(['tenant_id', 'provider_id']);
        });

        // 3. Upstream Carrier Monthly Invoices / Bills
        Schema::create('tenant_upstream_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
            $table->foreignId('link_id')->nullable()->constrained('tenant_upstream_links')->nullOnDelete();
            $table->string('invoice_no');
            $table->date('billing_month');
            $table->decimal('bandwidth_cost', 12, 2)->default(0);
            $table->decimal('transmission_cost', 12, 2)->default(0);
            $table->decimal('vat_tax', 12, 2)->default(0);
            $table->decimal('other_charges', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('UNPAID'); // UNPAID, PARTIAL, PAID
            $table->date('due_date')->nullable();
            $table->json('item_breakdown')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'billing_month']);
            $table->index(['tenant_id', 'provider_id']);
            $table->index(['tenant_id', 'payment_status']);
        });

        // 4. Upstream Payment Vouchers & Disbursement Records
        Schema::create('tenant_upstream_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('tenant_upstream_providers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('tenant_upstream_invoices')->nullOnDelete();
            $table->string('voucher_no');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->default('BANK_TRANSFER'); // BANK_TRANSFER, CHEQUE, RTGS, CASH, OTHER
            $table->string('bank_name')->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('receipt_attachment')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider_id']);
            $table->index(['tenant_id', 'invoice_id']);
            $table->index(['tenant_id', 'voucher_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_upstream_payments');
        Schema::dropIfExists('tenant_upstream_invoices');
        Schema::dropIfExists('tenant_upstream_links');
        Schema::dropIfExists('tenant_upstream_providers');
    }
};
