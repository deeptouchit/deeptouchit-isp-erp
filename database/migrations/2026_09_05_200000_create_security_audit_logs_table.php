<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100); // auth_failed, auth_success, brute_force_blocked, webhook_tampered, backup_created, backup_restored, rate_limit_hit, tenant_suspended, wallet_adjusted
                $table->enum('severity', ['info', 'warning', 'critical', 'alert'])->default('info');
                $table->string('actor_type', 50)->default('system'); // owner, tenant_admin, system, guest
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('request_method', 10)->nullable();
                $table->text('request_url')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('description');
                $table->json('details')->nullable();
                $table->string('status', 30)->default('logged'); // logged, blocked, alerted, resolved
                $table->timestamps();

                $table->index(['event_type', 'severity']);
                $table->index('ip_address');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};
