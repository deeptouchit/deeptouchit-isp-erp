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
        Schema::create('tenant_field_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('job_number')->unique();
            $table->string('job_type')->default('fiber_splicing'); // fiber_splicing, onu_replacement, home_visit, cable_repair, pop_maintenance, new_connection
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('address')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index(); // Technician User ID
            $table->string('assigned_technician_name')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'dispatched', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('issue_description')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->string('optical_rx_before')->nullable();
            $table->string('optical_rx_after')->nullable();
            $table->text('materials_used')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_field_jobs');
    }
};
