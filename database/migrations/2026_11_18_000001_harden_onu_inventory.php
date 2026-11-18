<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_onus', fn (Blueprint $table) => $table->enum('status', ['online', 'offline', 'los', 'unregistered', 'unknown'])->default('unknown')->change());
        foreach (['name', 'desc', 'local_name', 'local_desc', 'vendor', 'model', 'hardware_version', 'software_version', 'onu_type', 'vlan_mode', 'service_mode', 'lan1_state', 'wifi_ssid', 'catv_state', 'bandwidth_profile'] as $column) {
            if (! Schema::hasColumn('tenant_onus', $column)) {
                Schema::table('tenant_onus', fn (Blueprint $table) => $table->text($column)->nullable());
            }
        }
        foreach (['rx_power_dbm', 'tx_power_dbm'] as $column) {
            if (! Schema::hasColumn('tenant_onus', $column)) {
                Schema::table('tenant_onus', fn (Blueprint $table) => $table->double($column)->nullable());
            }
        }
        foreach (['distance_m', 'running_state', 'control_flag'] as $column) {
            if (! Schema::hasColumn('tenant_onus', $column)) {
                Schema::table('tenant_onus', fn (Blueprint $table) => $table->integer($column)->nullable());
            }
        }
        foreach (['last_online_at', 'last_offline_at', 'optical_observed_at'] as $column) {
            if (! Schema::hasColumn('tenant_onus', $column)) {
                Schema::table('tenant_onus', fn (Blueprint $table) => $table->timestamp($column)->nullable());
            }
        }
        foreach (['pppoe_password', 'wifi_password'] as $column) {
            $exists = Schema::hasColumn('tenant_onus', $column);
            Schema::table('tenant_onus', function (Blueprint $table) use ($column, $exists) {
                $definition = $table->text($column)->nullable();
                if ($exists) {
                    $definition->change();
                }
            });
            DB::table('tenant_onus')->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($column) {
                foreach ($rows as $row) {
                    if ($row->$column === '') {
                        continue;
                    }
                    try {
                        Crypt::decryptString($row->$column);
                    } catch (DecryptException $e) {
                        DB::table('tenant_onus')->where('id', $row->id)->update([$column => Crypt::encryptString($row->$column)]);
                    }
                }
            });
        }
        if (! Schema::hasColumn('tenant_olts', 'pon_port_map')) {
            Schema::table('tenant_olts', fn (Blueprint $table) => $table->json('pon_port_map')->nullable());
        }
        if (! Schema::hasColumn('tenant_customers', 'onu_mac_sn')) {
            Schema::table('tenant_customers', fn (Blueprint $table) => $table->string('onu_mac_sn')->nullable()->index());
        }
        foreach (['vendor', 'serial_number', 'hardware_version', 'firmware_version', 'mac_address', 'web_username', 'web_password'] as $column) {
            if (! Schema::hasColumn('tenant_olts', $column)) {
                Schema::table('tenant_olts', fn (Blueprint $table) => $table->text($column)->nullable());
            }
        }
        if (! Schema::hasColumn('tenant_olts', 'web_port')) {
            Schema::table('tenant_olts', fn (Blueprint $table) => $table->integer('web_port')->nullable());
        }
    }

    public function down(): void
    {
        // Intentionally preserve inventory and encrypted secrets on rollback.
    }
};
