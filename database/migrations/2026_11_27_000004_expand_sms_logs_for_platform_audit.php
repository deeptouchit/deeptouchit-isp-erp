<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::table('sms_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('sms_logs', 'sender_id')) {
                $table->string('sender_id', 100)->nullable()->after('recipient_phone');
            }
            if (! Schema::hasColumn('sms_logs', 'source')) {
                $table->string('source', 100)->nullable()->after('sms_type');
            }
            if (! Schema::hasColumn('sms_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::table('sms_logs', function (Blueprint $table): void {
            foreach (['sender_id', 'source', 'ip_address'] as $column) {
                if (Schema::hasColumn('sms_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
