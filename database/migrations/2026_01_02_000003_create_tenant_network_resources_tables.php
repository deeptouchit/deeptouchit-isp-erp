<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_ip_pools')) {
            Schema::create('tenant_ip_pools', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('name');
                $table->string('network_cidr');
                $table->string('start_ip');
                $table->string('end_ip');
                $table->string('gateway_ip')->nullable();
                $table->string('dns_servers')->nullable();
                $table->integer('total_ips')->default(0);
                $table->integer('used_ips')->default(0);
                $table->enum('type', ['static', 'pppoe', 'dhcp'])->default('pppoe');
                $table->enum('status', ['active', 'full', 'inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_vlans')) {
            Schema::create('tenant_vlans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('name');
                $table->integer('vlan_id');
                $table->text('description')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->index(['tenant_id', 'vlan_id']);
            });
        }

        if (!Schema::hasTable('tenant_routes')) {
            Schema::create('tenant_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('destination_cidr');
                $table->string('gateway_ip');
                $table->string('interface')->nullable();
                $table->integer('distance')->default(1);
                $table->string('routing_mark')->nullable();
                $table->enum('status', ['active', 'disabled'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_internet_packages')) {
            Schema::create('tenant_internet_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->string('name');
                $table->string('mikrotik_profile');
                $table->decimal('rate_limit_upload_mbps', 8, 2)->default(10.00);
                $table->decimal('rate_limit_download_mbps', 8, 2)->default(10.00);
                $table->decimal('price', 10, 2)->default(0.00);
                $table->string('billing_cycle')->default('monthly');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_internet_packages');
        Schema::dropIfExists('tenant_routes');
        Schema::dropIfExists('tenant_vlans');
        Schema::dropIfExists('tenant_ip_pools');
    }
};
