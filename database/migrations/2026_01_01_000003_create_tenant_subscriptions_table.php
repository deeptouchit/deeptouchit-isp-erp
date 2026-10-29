<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('saas_plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
                $table->string('plan_name');
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->date('starts_at');
                $table->date('expires_at');
                $table->date('trial_ends_at')->nullable();
                $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('active');
                $table->boolean('auto_renew')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
