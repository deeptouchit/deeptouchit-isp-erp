<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payment_intents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('tenant_customer_invoices')->cascadeOnDelete();
            $table->string('idempotency_key', 255);
            $table->decimal('amount', 19, 4);
            $table->string('currency', 10);
            $table->string('gateway', 40)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_intents');
    }
};
