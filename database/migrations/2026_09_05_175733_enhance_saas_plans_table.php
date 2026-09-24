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
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('id');
            $table->decimal('otc_charge', 10, 2)->default(0.00)->after('monthly_price');
            $table->decimal('premium_monthly_price', 10, 2)->nullable()->after('otc_charge');
            $table->integer('olt_limit')->default(1)->after('mikrotik_limit');
            $table->integer('trial_days')->default(14)->after('features');
            $table->boolean('is_popular')->default(false)->after('trial_days');
            $table->string('badge_text', 50)->nullable()->after('is_popular');
            $table->integer('sort_order')->default(0)->after('badge_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn([
                'code',
                'otc_charge',
                'premium_monthly_price',
                'olt_limit',
                'trial_days',
                'is_popular',
                'badge_text',
                'sort_order',
            ]);
        });
    }
};
