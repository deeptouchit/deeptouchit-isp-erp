<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('network_audit_logs') && Schema::hasColumn('network_audit_logs', 'tenant_id')) {
            Schema::table('network_audit_logs', function (Blueprint $table): void {
                $table->foreignId('tenant_id')->nullable()->change();
                if (! Schema::hasColumn('network_audit_logs', 'device_type')) {
                    $table->string('device_type')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'device_id')) {
                    $table->unsignedBigInteger('device_id')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'device_name')) {
                    $table->string('device_name')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'command_executed')) {
                    $table->text('command_executed')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'response_summary')) {
                    $table->text('response_summary')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'ip_address')) {
                    $table->string('ip_address', 45)->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'user_agent')) {
                    $table->text('user_agent')->nullable();
                }
                if (! Schema::hasColumn('network_audit_logs', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('network_audit_logs') && Schema::hasColumn('network_audit_logs', 'tenant_id')) {
            Schema::table('network_audit_logs', function (Blueprint $table): void {
                $table->foreignId('tenant_id')->nullable(false)->change();
            });
        }
    }
};
