<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh-install definitions verified against live schema on 2026-11-17.
        // Existing tables are never altered or backfilled by this migration.
        if (!Schema::hasTable('tenant_routers')) {
            Schema::create('tenant_routers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name', 255);
                $table->string('model', 255)->nullable();
                $table->string('ros_version', 255)->nullable();
                $table->enum('connection_type', ['api', 'radius', 'hybrid'])->default('api');
                $table->string('ip_address', 255);
                $table->integer('api_port')->nullable();
                $table->integer('coa_port')->default('3799');
                $table->boolean('use_ssl')->default('0');
                $table->string('username', 255)->nullable();
                $table->text('password')->nullable();
                $table->text('radius_secret')->nullable();
                $table->string('wireguard_ip', 255)->nullable();
                $table->string('wireguard_public_key', 255)->nullable();
                $table->enum('status', ['online', 'offline', 'error', 'pending'])->default('pending');
                $table->boolean('is_active')->default('1');
                $table->integer('cpu_load')->nullable();
                $table->bigInteger('free_memory')->nullable();
                $table->bigInteger('total_memory')->nullable();
                $table->string('uptime', 255)->nullable();
                $table->timestamp('last_ping_at')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['is_active'], 'tenant_routers_is_active_index');
                $table->index(['tenant_id', 'status'], 'tenant_routers_tenant_id_status_index');
                $table->foreign(['tenant_id'], 'tenant_routers_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_routers');
    }
};
