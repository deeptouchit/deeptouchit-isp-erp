<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2);
            $table->enum('applies_to', ['all', 'specific_plans', 'renewals_only', 'first_order_only'])->default('all');
            $table->json('plan_ids')->nullable();
            $table->json('billing_cycles')->nullable();
            $table->decimal('min_order_amount', 10, 2)->default(0.00);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('user_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['code', 'is_active'], 'idx_coupons_active');
            $table->index('expires_at', 'idx_coupons_expiry');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
