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
        Schema::table('tenant_onus', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_onus', 'vlan_id')) {
                $table->integer('vlan_id')->nullable()->after('onu_type');
            }
            if (!Schema::hasColumn('tenant_onus', 'vlan_mode')) {
                $table->string('vlan_mode', 50)->nullable()->default('tag')->after('vlan_id');
            }
            if (!Schema::hasColumn('tenant_onus', 'service_mode')) {
                $table->string('service_mode', 50)->nullable()->default('bridge')->after('vlan_mode');
            }
            if (!Schema::hasColumn('tenant_onus', 'pppoe_username')) {
                $table->string('pppoe_username', 100)->nullable()->after('service_mode');
            }
            if (!Schema::hasColumn('tenant_onus', 'pppoe_password')) {
                $table->string('pppoe_password', 100)->nullable()->after('pppoe_username');
            }
            if (!Schema::hasColumn('tenant_onus', 'lan1_state')) {
                $table->string('lan1_state', 20)->nullable()->default('enable')->after('pppoe_password');
            }
            if (!Schema::hasColumn('tenant_onus', 'wifi_ssid')) {
                $table->string('wifi_ssid', 100)->nullable()->after('lan1_state');
            }
            if (!Schema::hasColumn('tenant_onus', 'wifi_password')) {
                $table->string('wifi_password', 100)->nullable()->after('wifi_ssid');
            }
            if (!Schema::hasColumn('tenant_onus', 'catv_state')) {
                $table->string('catv_state', 20)->nullable()->default('enable')->after('wifi_password');
            }
            if (!Schema::hasColumn('tenant_onus', 'bandwidth_profile')) {
                $table->string('bandwidth_profile', 100)->nullable()->after('catv_state');
            }
            if (!Schema::hasColumn('tenant_onus', 'config_payload')) {
                $table->json('config_payload')->nullable()->after('bandwidth_profile');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_onus', function (Blueprint $table) {
            $table->dropColumn([
                'vlan_id',
                'vlan_mode',
                'service_mode',
                'pppoe_username',
                'pppoe_password',
                'lan1_state',
                'wifi_ssid',
                'wifi_password',
                'catv_state',
                'bandwidth_profile',
                'config_payload',
            ]);
        });
    }
};
