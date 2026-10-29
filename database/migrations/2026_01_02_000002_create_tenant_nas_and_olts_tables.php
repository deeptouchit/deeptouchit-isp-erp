<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_nas')) {
            Schema::create('tenant_nas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('nasname');
                $table->string('shortname')->nullable();
                $table->string('type')->default('mikrotik');
                $table->integer('ports')->nullable();
                $table->string('secret');
                $table->string('server')->nullable();
                $table->string('community')->nullable();
                $table->string('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_olts')) {
            Schema::create('tenant_olts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('name');
                $table->string('brand'); // Huawei, ZTE, VSOL, BDCOM
                $table->string('model')->nullable();
                $table->string('ip_address');
                $table->integer('snmp_port')->default(161);
                $table->string('snmp_community')->default('public');
                $table->string('snmp_version')->default('v2c');
                $table->integer('telnet_port')->default(23);
                $table->string('telnet_username')->nullable();
                $table->text('telnet_password')->nullable();
                $table->integer('ssh_port')->default(22);
                $table->integer('pon_ports_count')->default(8);
                $table->integer('total_pon_ports')->default(8);
                $table->enum('status', ['online', 'offline', 'error', 'pending'])->default('pending');
                $table->integer('total_onus_count')->default(0);
                $table->integer('online_onus_count')->default(0);
                $table->integer('offline_onus_count')->default(0);
                $table->integer('los_onus_count')->default(0);
                $table->integer('cpu_load')->nullable();
                $table->integer('memory_usage')->nullable();
                $table->double('temperature')->nullable();
                $table->unsignedTinyInteger('license_limit')->default(0);
                $table->unsignedInteger('license_time_hours')->default(0);
                $table->boolean('license_auto_renew')->default(false);
                $table->unsignedInteger('license_renew_threshold_hours')->default(168);
                $table->unsignedInteger('license_renew_days')->default(0);
                $table->text('license_auth_password')->nullable();
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
        Schema::dropIfExists('tenant_olts');
        Schema::dropIfExists('tenant_nas');
    }
};
