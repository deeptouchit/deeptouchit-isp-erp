<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('saas_invoices')) {
            Schema::create('saas_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->date('billing_period_start')->nullable();
                $table->date('billing_period_end')->nullable();
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('total', 10, 2)->default(0.00);
                $table->enum('status', ['paid', 'unpaid', 'overdue', 'cancelled'])->default('unpaid');
                $table->date('due_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('transaction_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subscription_invoice_items')) {
            Schema::create('subscription_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('saas_invoice_id')->constrained('saas_invoices')->cascadeOnDelete();
                $table->string('item_type')->default('plan_subscription');
                $table->string('description');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('total', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_items');
        Schema::dropIfExists('saas_invoices');
    }
};
