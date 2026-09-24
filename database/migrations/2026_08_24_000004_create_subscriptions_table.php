<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('hosting_plans')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
            $table->string('domain', 255);
            $table->string('username', 50)->unique();
            $table->string('document_root', 255);
            $table->string('php_version', 10)->default('8.2');
            $table->enum('status', ['active', 'suspended', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->enum('period', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('price', 10, 2);
            $table->date('next_billing_date');
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_subscriptions_user_status');
            $table->index(['next_billing_date', 'status'], 'idx_subscriptions_next_billing');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
