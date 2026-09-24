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
        Schema::create('tenant_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
            $table->string('dst_address', 100)->default('0.0.0.0/0'); // Destination Subnet / CIDR (e.g. 0.0.0.0/0, 10.0.0.0/8)
            $table->string('gateway', 100); // Gateway IP or interface (e.g. 103.145.10.1, ether1-wan)
            $table->string('routing_table', 50)->default('main'); // main, ISP1_Table, BDIX, etc.
            $table->integer('distance')->default(1); // Administrative distance / metric
            $table->string('type', 50)->default('static'); // static, bgp, ospf, connected, blackhole
            $table->integer('scope')->default(30);
            $table->integer('target_scope')->default(10);
            $table->string('status', 30)->default('active'); // active, disabled
            $table->text('description')->nullable(); // MikroTik comment
            $table->boolean('is_sync_mikrotik')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'router_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'dst_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_routes');
    }
};
