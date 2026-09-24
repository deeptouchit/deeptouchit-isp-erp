<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('backup_storage_id')->nullable()->constrained('backup_storages')->nullOnDelete();
            $table->enum('frequency', ['hourly', 'daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->string('cron_expression', 100)->default('0 2 * * *');
            $table->enum('type', ['full', 'files', 'database', 'incremental'])->default('full');
            $table->integer('retention_count')->default(7);
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 50)->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('notify_on_failure')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
