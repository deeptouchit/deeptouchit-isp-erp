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
        Schema::create('domain_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->enum('target_type', ['parked', 'redirect'])->default('parked');
            $table->string('redirect_url')->nullable();
            $table->unsignedSmallInteger('redirect_status_code')->default(301);
            $table->enum('ssl_status', ['none', 'active', 'pending', 'expired'])->default('none');
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('ssl_last_renewed_at')->nullable();
            $table->boolean('auto_ssl')->default(true);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_aliases');
    }
};
