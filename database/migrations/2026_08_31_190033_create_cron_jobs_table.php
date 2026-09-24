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
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('command');
            $table->string('cron_expression', 100)->default('* * * * *');
            $table->string('description')->nullable();
            $table->string('output_handling', 50)->default('log_file'); // discard, log_file, email
            $table->string('log_file_path')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('run_as_user', 50)->default('www-data');
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status', 30)->nullable(); // success, failed, running
            $table->integer('last_run_duration_ms')->nullable();
            $table->text('last_output_preview')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_jobs');
    }
};
