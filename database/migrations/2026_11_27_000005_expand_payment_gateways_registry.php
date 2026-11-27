<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_gateways')) return;
        Schema::table('payment_gateways', function (Blueprint $table): void {
            foreach (['api_url', 'callback_url', 'success_url', 'fail_url', 'cancel_url', 'webhook_url'] as $column) {
                if (! Schema::hasColumn('payment_gateways', $column)) $table->text($column)->nullable();
            }
            if (! Schema::hasColumn('payment_gateways', 'last_test_status')) $table->string('last_test_status', 30)->nullable();
            if (! Schema::hasColumn('payment_gateways', 'last_tested_at')) $table->timestamp('last_tested_at')->nullable();
        });
    }

    public function down(): void {}
};
