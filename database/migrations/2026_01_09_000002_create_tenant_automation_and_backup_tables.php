<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_automation_settings')) {
            Schema::create('tenant_automation_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('key');
                $table->text('value')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'key']);
            });
        }

        if (!Schema::hasTable('tenant_backups')) {
            Schema::create('tenant_backups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('disk')->default('local');
                $table->bigInteger('size_bytes')->default(0);
                $table->enum('status', ['completed', 'failed', 'in_progress'])->default('completed');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_backups');
        Schema::dropIfExists('tenant_automation_settings');
    }
};
