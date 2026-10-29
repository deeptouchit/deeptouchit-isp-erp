<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_onus')) {
            Schema::create('tenant_onus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('olt_id')->constrained('tenant_olts')->cascadeOnDelete();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('router_mac')->nullable()->index();
                $table->string('pon_port', 20);
                $table->integer('onu_id')->nullable();
                $table->string('mac_address', 50)->nullable()->index();
                $table->string('serial_number', 50)->nullable()->index();
                $table->double('rx_power')->nullable();
                $table->double('tx_power')->nullable();
                $table->enum('status', ['online', 'offline', 'los', 'unregistered'])->default('offline');
                $table->string('vlan_id')->nullable();
                $table->string('mgmt_ip')->nullable();
                $table->string('pppoe_username')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_onus');
    }
};
