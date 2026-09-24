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
        Schema::create('tenant_installation_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('task_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('applicant_name');
            $table->string('applicant_phone');
            $table->string('installation_address');
            $table->unsignedBigInteger('zone_id')->nullable()->index();
            $table->unsignedBigInteger('package_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_technician_id')->nullable()->index();
            $table->string('assigned_technician_name')->nullable();
            $table->string('installation_stage')->default('feasibility_check'); // feasibility_check, cable_pulling, splicing_power_test, mikrotik_binding, active_completed, cancelled
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('cable_length_meters')->default(0);
            $table->string('splitter_location')->nullable();
            $table->string('onu_model')->nullable();
            $table->string('onu_mac_serial')->nullable();
            $table->string('optical_rx_power')->nullable();
            $table->decimal('connection_fee', 10, 2)->default(0.00);
            $table->decimal('advance_payment', 10, 2)->default(0.00);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_installation_tasks');
    }
};
