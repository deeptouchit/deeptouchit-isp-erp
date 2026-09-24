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
        Schema::table('hosting_plans', function (Blueprint $table) {
            $table->boolean('allow_redis')->default(false)->after('allow_git_deploy');
            $table->integer('redis_memory_mb')->default(64)->after('allow_redis');
            $table->boolean('allow_memcached')->default(false)->after('redis_memory_mb');
            $table->boolean('allow_nodejs')->default(false)->after('allow_memcached');
            $table->boolean('allow_python')->default(false)->after('allow_nodejs');
            $table->boolean('allow_cron_jobs')->default(true)->after('allow_python');
            $table->boolean('allow_backups')->default(true)->after('allow_cron_jobs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_plans', function (Blueprint $table) {
            $table->dropColumn([
                'allow_redis',
                'redis_memory_mb',
                'allow_memcached',
                'allow_nodejs',
                'allow_python',
                'allow_cron_jobs',
                'allow_backups',
            ]);
        });
    }
};
