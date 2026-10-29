<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_staff_attendances')) {
            Schema::create('tenant_staff_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('date');
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->enum('status', ['present', 'late', 'absent', 'on_leave'])->default('present');
                $table->integer('late_minutes')->default(0);
                $table->string('ip_address', 45)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'user_id', 'date']);
            });
        }

        if (!Schema::hasTable('tenant_field_jobs')) {
            Schema::create('tenant_field_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('job_no')->unique();
                $table->string('title');
                $table->enum('type', ['installation', 'repair', 'maintenance', 'inspection'])->default('installation');
                $table->foreignId('customer_id')->nullable()->constrained('tenant_customers')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('tenant_installation_tasks')) {
            Schema::create('tenant_installation_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('task_no')->unique();
                $table->foreignId('customer_id')->constrained('tenant_customers')->cascadeOnDelete();
                $table->foreignId('field_job_id')->nullable()->constrained('tenant_field_jobs')->nullOnDelete();
                $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
                $table->integer('cable_length_meters')->default(0);
                $table->string('fiber_core_color')->nullable();
                $table->string('splitter_port')->nullable();
                $table->string('distribution_box')->nullable();
                $table->double('dbm_reading')->nullable();
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_installation_tasks');
        Schema::dropIfExists('tenant_field_jobs');
        Schema::dropIfExists('tenant_staff_attendances');
    }
};
