<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('automation_logs')) {
            Schema::create('automation_logs', function (Blueprint $table) {
                $table->id();
                $table->string('task_name'); // e.g. billing_engine, invoice_generation, suspension_check, reminder_dispatch, system_heartbeat
                $table->string('triggered_by')->default('cron'); // cron, manual, webhook
                $table->string('status')->default('success'); // success, failed, warning
                $table->unsignedInteger('duration_ms')->default(0);
                $table->json('metrics')->nullable(); // counts of processed items
                $table->text('output_summary')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
