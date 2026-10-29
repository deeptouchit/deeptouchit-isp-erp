<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('saas_plans')) {
            Schema::create('saas_plans', function (Blueprint $table) {
                $table->id();
                $table->string('code', 20)->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('monthly_price', 10, 2)->default(0.00);
                $table->decimal('otc_charge', 10, 2)->default(0.00);
                $table->decimal('premium_monthly_price', 10, 2)->nullable();
                $table->decimal('yearly_price', 10, 2)->default(0.00);
                $table->integer('customer_limit')->default(500);
                $table->integer('mikrotik_limit')->default(2);
                $table->integer('olt_limit')->default(1);
                $table->boolean('allow_radius')->default(false);
                $table->boolean('allow_wireguard')->default(false);
                $table->boolean('allow_snmp_monitoring')->default(true);
                $table->integer('reseller_limit')->default(5);
                $table->json('features')->nullable();
                $table->integer('trial_days')->default(14);
                $table->boolean('is_popular')->default(false);
                $table->string('badge_text', 50)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_plans');
    }
};
