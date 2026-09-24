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
        Schema::create('tenant_vlans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->foreignId('olt_id')->nullable()->constrained('tenant_olts')->nullOnDelete();
            $table->integer('vlan_id'); // 1 - 4094
            $table->string('name', 100);
            $table->string('interface', 100)->default('ether1'); // Parent interface in MikroTik e.g. ether1, bridge, sfp1
            $table->string('type', 50)->default('service'); // service, management, corporate, cgnat, voice, tr069
            $table->string('subnet', 100)->nullable();
            $table->string('gateway', 100)->nullable();
            $table->boolean('dhcp_enabled')->default(false);
            $table->integer('mtu')->default(1500);
            $table->string('status', 30)->default('active'); // active, disabled
            $table->text('description')->nullable(); // MikroTik comment
            $table->boolean('is_sync_mikrotik')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vlan_id']);
            $table->index(['tenant_id', 'router_id']);
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_vlans');
    }
};
