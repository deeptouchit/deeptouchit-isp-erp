<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_customer_payments')) {
            Schema::create('tenant_customer_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('customer_id')->constrained('tenant_customers')->onDelete('cascade');
                $table->string('invoice_no', 50)->index();
                $table->string('billing_month', 50); // e.g. September-2026
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->string('payment_method', 50)->default('cash'); // cash, bkash, nagad, bank, online
                $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
                $table->string('status', 30)->default('paid'); // paid, pending, failed, refunded
                $table->timestamp('paid_at')->nullable();
                $table->string('transaction_id', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_customer_payments');
    }
};
