<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->change();
            $table->string('name', 255)->nullable()->after('subscription_id');
            $table->string('storage_driver', 50)->default('local')->after('remote_path');
        });
    }

    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            $table->dropColumn(['name', 'storage_driver']);
        });
    }
};
