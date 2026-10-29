<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_routers')) {
            Schema::create('tenant_routers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('model')->nullable();
                $table->string('ip_address');
                $table->integer('api_port')->default(8728);
                $table->string('api_username');
                $table->text('api_password');
                $table->boolean('is_active')->default(true);
                $table->enum('status', ['online', 'offline', 'error', 'pending'])->default('pending');
                $table->string('uptime')->nullable();
                $table->timestamp('last_ping_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_routers');
    }
};
