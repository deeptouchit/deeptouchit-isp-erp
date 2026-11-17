<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fresh-install definitions verified against live schema on 2026-11-17.
        // Existing tables are never altered or backfilled by this migration.
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('key', 255);
                $table->longText('value')->nullable();
                $table->string('group', 255)->default('general');
                $table->string('type', 255)->default('string');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['group'], 'settings_group_index');
                $table->index(['key'], 'settings_key_index');
                $table->unique(['tenant_id', 'key'], 'settings_tenant_id_key_unique');
                $table->foreign(['tenant_id'], 'settings_tenant_id_foreign')->references(['id'])->on('tenants')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
