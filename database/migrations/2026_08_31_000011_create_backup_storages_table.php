<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_storages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('driver', ['local', 's3', 'sftp', 'wasabi', 'r2', 'google_drive'])->default('local');
            $table->string('path', 255)->default('/var/backups/deeptouchhost');
            $table->json('credentials')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->unsignedBigInteger('capacity_bytes')->default(0);
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->integer('retention_days')->default(30);
            $table->boolean('encryption_enabled')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_result', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_storages');
    }
};
