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
        // 1. Tenant Automation & Auto-Cut Configuration Table
        if (!Schema::hasTable('tenant_automation_settings')) {
            Schema::create('tenant_automation_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                
                // Monthly Billing Generation
                $table->boolean('auto_billing_enabled')->default(true);
                $table->integer('billing_generation_day')->default(1); // 1st of month
                $table->string('billing_generation_time')->default('00:00');
                $table->boolean('auto_send_bill_sms')->default(true);
                
                // Auto-Cut & Line Suspension
                $table->boolean('auto_cut_enabled')->default(true);
                $table->integer('grace_period_days')->default(7); // Cuts on 8th
                $table->decimal('min_due_threshold', 10, 2)->default(50.00);
                $table->string('auto_cut_action')->default('disable_secret'); // disable_secret, radius_pool, change_profile
                $table->string('auto_cut_time')->default('02:00');
                $table->boolean('auto_send_cut_sms')->default(true);

                // Auto-Reconnection
                $table->boolean('auto_reconnect_enabled')->default(true);
                $table->boolean('auto_send_restore_sms')->default(true);

                // Expiry Reminders
                $table->boolean('expiry_reminders_enabled')->default(true);
                $table->integer('reminder_1_days_before')->default(3);
                $table->integer('reminder_2_days_before')->default(1);
                $table->string('reminder_dispatch_time')->default('10:00');

                // Automated Backup
                $table->boolean('auto_backup_enabled')->default(true);
                $table->string('backup_frequency')->default('daily'); // daily, weekly
                $table->string('backup_time')->default('03:30');
                $table->integer('backup_retention_days')->default(30);

                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }

        // 2. Add tenant_id to automation_logs if missing
        if (Schema::hasTable('automation_logs') && !Schema::hasColumn('automation_logs', 'tenant_id')) {
            Schema::table('automation_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_automation_settings');
        if (Schema::hasTable('automation_logs') && Schema::hasColumn('automation_logs', 'tenant_id')) {
            Schema::table('automation_logs', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
