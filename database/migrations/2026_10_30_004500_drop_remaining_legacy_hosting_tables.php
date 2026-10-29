<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'monitoring_alert_rules',
            'monitoring_alert_states',
            'backup_schedules',
            'backup_storages',
            'canned_responses',
            'client_credits',
            'coupons',
            'login_histories',
            'support_agent_profiles',
            'support_departments',
            'subscriptions',
            'invoices',
            'invoice_items',
            'payments',
            'transactions',
            'activity_logs',
            'announcements',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tables are permanently purged legacy dead code.
    }
};
