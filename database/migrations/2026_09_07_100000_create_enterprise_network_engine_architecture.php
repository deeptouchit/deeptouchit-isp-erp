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
        // 1. Enhance SaasPlans table with network quota attributes
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->boolean('allow_radius')->default(false)->after('olt_limit');
            $table->boolean('allow_wireguard')->default(false)->after('allow_radius');
            $table->boolean('allow_snmp_monitoring')->default(true)->after('allow_wireguard');
        });

        // 2. Tenant Routers table (MikroTik Fleet)
        Schema::create('tenant_routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('ros_version')->nullable();
            $table->enum('connection_type', ['api', 'radius', 'hybrid'])->default('api');
            $table->string('ip_address');
            $table->integer('api_port')->default(8728);
            $table->boolean('use_ssl')->default(false);
            $table->string('username')->default('admin');
            $table->text('password'); // Encrypted with Crypt
            $table->text('radius_secret')->nullable(); // Encrypted with Crypt
            $table->string('wireguard_ip')->nullable();
            $table->string('wireguard_public_key')->nullable();
            $table->enum('status', ['online', 'offline', 'error', 'pending'])->default('pending');
            $table->integer('cpu_load')->nullable();
            $table->bigInteger('free_memory')->nullable();
            $table->bigInteger('total_memory')->nullable();
            $table->string('uptime')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 3. Tenant OLTs table (PON Access Hardware)
        Schema::create('tenant_olts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->string('name');
            $table->enum('brand', ['vsol', 'bdcom', 'huawei', 'zte', 'fiberhome', 'other'])->default('vsol');
            $table->string('model')->nullable();
            $table->string('hardware_version')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->text('snmp_community'); // Encrypted with Crypt
            $table->enum('snmp_version', ['v1', 'v2c', 'v3'])->default('v2c');
            $table->integer('telnet_port')->default(23);
            $table->string('telnet_username')->nullable();
            $table->text('telnet_password')->nullable(); // Encrypted with Crypt
            $table->integer('ssh_port')->default(22);
            $table->integer('pon_ports_count')->default(4);
            $table->enum('status', ['online', 'offline', 'error', 'pending'])->default('pending');
            $table->integer('total_onus_count')->default(0);
            $table->integer('online_onus_count')->default(0);
            $table->integer('offline_onus_count')->default(0);
            $table->integer('los_onus_count')->default(0);
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 4. Immutable Network Audit Logs
        Schema::create('network_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('device_type', ['router', 'olt', 'onu', 'radius', 'wireguard', 'system'])->default('router');
            $table->unsignedBigInteger('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('action'); // ROUTER_REBOOT, ONU_RESET, COA_DISCONNECT, etc.
            $table->enum('status', ['success', 'failed', 'pending'])->default('success');
            $table->text('command_executed')->nullable();
            $table->text('response_summary')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'device_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_audit_logs');
        Schema::dropIfExists('tenant_olts');
        Schema::dropIfExists('tenant_routers');

        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn(['allow_radius', 'allow_wireguard', 'allow_snmp_monitoring']);
        });
    }
};
