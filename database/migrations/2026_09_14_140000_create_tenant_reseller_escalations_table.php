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
        Schema::create('tenant_reseller_escalations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('escalation_number')->unique();
            $table->unsignedBigInteger('reseller_id')->index();
            $table->string('reseller_name');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('category')->default('trunk_congestion'); // trunk_congestion, bgp_routing, radius_sync, vlan_allocation, wholesale_billing, olt_uplink_loss
            $table->string('subject');
            $table->enum('impact_level', ['critical_outage', 'high_degraded', 'medium_packet_loss', 'low_inquiry'])->default('high_degraded');
            $table->enum('status', ['open', 'tier2_investigating', 'tier3_noc_escalated', 'resolved', 'closed'])->default('open');
            $table->unsignedBigInteger('assigned_engineer_id')->nullable()->index();
            $table->string('assigned_engineer_name')->nullable();
            $table->string('affected_circuits')->nullable();
            $table->text('issue_description')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_reseller_escalations');
    }
};
