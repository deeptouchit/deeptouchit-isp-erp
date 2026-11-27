<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider', 50);
            $table->string('sender_id', 100)->nullable();
            $table->string('custom_url', 1000)->nullable();
            $table->json('credentials')->nullable();
            $table->decimal('rate_per_part', 10, 4)->default(0);
            $table->boolean('is_active')->default(false);
            $table->decimal('last_balance', 12, 2)->nullable();
            $table->timestamp('last_balance_checked_at')->nullable();
            $table->text('last_test_error')->nullable();
            $table->timestamps();
            $table->index(['provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_sms_gateways');
    }
};
