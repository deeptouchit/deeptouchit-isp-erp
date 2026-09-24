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
        if (!Schema::hasTable('tenant_backups')) {
            Schema::create('tenant_backups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('filename');
                $table->string('backup_type', 50)->default('full_database');
                $table->string('trigger_type', 50)->default('manual_admin');
                $table->string('storage_location', 50)->default('local_vault');
                $table->string('file_path')->nullable();
                $table->bigInteger('file_size_bytes')->default(0);
                $table->string('checksum_md5', 64)->nullable();
                $table->integer('tables_count')->default(0);
                $table->integer('records_count')->default(0);
                $table->string('status', 30)->default('completed');
                $table->text('log_summary')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
    }
};
