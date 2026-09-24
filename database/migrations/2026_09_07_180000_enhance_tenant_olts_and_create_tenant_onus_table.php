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
        // 1. Enhance tenant_olts table with Web API and live diagnostic telemetry
        Schema::table('tenant_olts', function (Blueprint $table) {
            $table->integer('web_port')->default(80)->after('ip_address');
            $table->string('web_username')->nullable()->default('root')->after('web_port');
            $table->text('web_password')->nullable()->after('web_username');
            $table->string('connection_type')->default('web_api')->after('web_password');
            $table->string('serial_number')->nullable()->after('connection_type');
            $table->string('mac_address')->nullable()->after('serial_number');
            $table->integer('total_pon_ports')->default(8)->after('pon_ports_count');
            $table->integer('cpu_load')->nullable()->after('los_onus_count');
            $table->integer('memory_usage')->nullable()->after('cpu_load');
            $table->float('temperature')->nullable()->after('memory_usage');
            $table->string('uptime')->nullable()->after('temperature');
        });

        // 2. Create tenant_onus table for deep subscriber optical line tracking
        Schema::create('tenant_onus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('olt_id')->constrained('tenant_olts')->cascadeOnDelete();
            $table->string('pon_port', 50)->default('0/1/1');
            $table->integer('onu_id')->default(1);
            $table->string('name')->nullable();
            $table->string('desc')->nullable();
            $table->string('mac_address', 50)->index();
            $table->string('vendor', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('hardware_version', 100)->nullable();
            $table->string('software_version', 100)->nullable();
            $table->enum('status', ['online', 'offline', 'alarm', 'unregistered'])->default('online');
            $table->integer('running_state')->default(1);
            $table->integer('control_flag')->default(1);
            $table->float('rx_power_dbm')->nullable();
            $table->float('tx_power_dbm')->nullable();
            $table->integer('distance_m')->nullable();
            $table->timestamp('last_online_at')->nullable();
            $table->timestamp('last_offline_at')->nullable();
            $table->string('offline_reason')->nullable();
            $table->timestamps();

            $table->unique(['olt_id', 'pon_port', 'onu_id'], 'olt_pon_onu_unique');
            $table->index(['tenant_id', 'status']);
            $table->index(['olt_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_onus');

        Schema::table('tenant_olts', function (Blueprint $table) {
            $table->dropColumn([
                'web_port',
                'web_username',
                'web_password',
                'connection_type',
                'serial_number',
                'mac_address',
                'total_pon_ports',
                'cpu_load',
                'memory_usage',
                'temperature',
                'uptime',
            ]);
        });
    }
};
