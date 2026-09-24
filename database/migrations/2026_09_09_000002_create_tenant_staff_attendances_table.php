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
        Schema::create('tenant_staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->onDelete('set null');
            $table->date('date')->index();
            $table->string('shift')->default('Morning Shift (09:00 - 18:00)');
            $table->dateTime('punch_in_at')->nullable();
            $table->dateTime('punch_out_at')->nullable();
            $table->string('status')->default('present'); // present, late, absent, on_leave, field_duty, half_day
            $table->integer('work_duration_minutes')->default(0);
            $table->string('punch_in_ip', 45)->nullable();
            $table->string('punch_out_ip', 45)->nullable();
            $table->string('punch_in_device', 191)->nullable();
            $table->text('late_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'date'], 'tenant_user_daily_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_staff_attendances');
    }
};
