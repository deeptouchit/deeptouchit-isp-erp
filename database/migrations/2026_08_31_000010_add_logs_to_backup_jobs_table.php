<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->string('trigger_source', 50)->default('manual')->after('storage_driver');
            $table->longText('log_output')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropColumn(['trigger_source', 'log_output']);
        });
    }
};
