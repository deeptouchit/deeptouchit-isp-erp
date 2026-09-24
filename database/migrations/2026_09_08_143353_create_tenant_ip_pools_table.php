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
        Schema::create('tenant_ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->string('name', 100);
            $table->string('pool_type', 50)->default('pppoe'); // pppoe, cgnat, static_public, dhcp, ipv6, vpn
            $table->string('ip_version', 20)->default('ipv4'); // ipv4, ipv6
            $table->string('range_start', 100);
            $table->string('range_end', 100);
            $table->string('cidr_subnet', 100)->nullable();
            $table->string('gateway', 100)->nullable();
            $table->string('dns_primary', 100)->nullable()->default('8.8.8.8');
            $table->string('dns_secondary', 100)->nullable()->default('1.1.1.1');
            $table->string('next_pool', 100)->nullable();
            $table->integer('total_ips')->default(0);
            $table->integer('used_ips')->default(0);
            $table->integer('vlan_id')->nullable();
            $table->boolean('is_sync_mikrotik')->default(false);
            $table->string('status', 30)->default('active'); // active, disabled, exhausted
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'pool_type']);
            $table->index(['tenant_id', 'router_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_ip_pools');
    }
};
