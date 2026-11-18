<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_automation_settings')) {
            Schema::create('tenant_automation_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
                $table->boolean('auto_billing_enabled')->default(true);
                $table->time('billing_generation_time')->default('00:05');
                $table->unsignedInteger('billing_generation_day')->default(3);
                $table->boolean('auto_cut_enabled')->default(true);
                $table->time('auto_cut_time')->default('02:00');
                $table->unsignedInteger('grace_period_days')->default(0);
                $table->decimal('min_due_threshold', 12, 2)->default(0);
                $table->string('auto_cut_action', 30)->default('suspend');
                $table->boolean('auto_send_cut_sms')->default(false);
                $table->boolean('auto_reconnect_enabled')->default(true);
                $table->boolean('auto_send_restore_sms')->default(false);
                $table->boolean('expiry_reminders_enabled')->default(false);
                $table->time('reminder_dispatch_time')->default('10:00');
                $table->unsignedInteger('reminder_1_days_before')->default(3);
                $table->unsignedInteger('reminder_2_days_before')->default(1);
                $table->boolean('auto_backup_enabled')->default(false);
                $table->time('backup_time')->default('03:00');
                $table->string('backup_frequency', 20)->default('daily');
                $table->unsignedInteger('backup_retention_days')->default(30);
                $table->string('backup_destination_email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_backups')) {
            Schema::create('tenant_backups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('disk')->default('local');
                $table->bigInteger('size_bytes')->default(0);
                $table->enum('status', ['completed', 'failed', 'in_progress'])->default('completed');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
        Schema::dropIfExists('tenant_automation_settings');
    }
};
