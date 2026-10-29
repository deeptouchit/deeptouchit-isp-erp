<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('transaction_id')->unique();
                $table->string('gateway');
                $table->string('payment_method')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('BDT');
                $table->enum('status', ['initiated', 'successful', 'failed', 'cancelled'])->default('initiated');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('raw_response')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_roles')) {
            Schema::create('tenant_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('display_name')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->json('permissions')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'slug']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_roles');
        Schema::dropIfExists('payment_transactions');
    }
};
