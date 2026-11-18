<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_daily_cash_handovers')) {
            Schema::create('tenant_daily_cash_handovers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('handover_no')->unique();
                $table->foreignId('collector_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('total_amount', 12, 2)->default(0.00);
                $table->integer('collections_count')->default(0);
                $table->date('handover_date');
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_customer_payments')) {
            Schema::create('tenant_customer_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('tenant_customer_invoices')->nullOnDelete();
                $table->foreignId('handover_id')->nullable()->constrained('tenant_daily_cash_handovers')->nullOnDelete();
                $table->string('invoice_no');
                $table->string('billing_month', 20)->nullable();
                $table->string('payment_method')->default('cash'); // cash, bkash, nagad, bank, online
                $table->string('transaction_id', 120)->nullable();
                $table->string('request_key', 100)->nullable();
                $table->decimal('amount', 10, 2);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('completed');
                $table->timestamp('paid_at')->nullable();
                $table->boolean('sms_sent')->default(false);
                $table->timestamp('sms_sent_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'created_at']);
                $table->unique(['tenant_id', 'invoice_no']);
                $table->unique(['tenant_id', 'request_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_customer_payments');
        Schema::dropIfExists('tenant_daily_cash_handovers');
    }
};
