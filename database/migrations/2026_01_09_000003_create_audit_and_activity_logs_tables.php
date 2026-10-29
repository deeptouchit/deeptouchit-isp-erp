<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_activity_logs')) {
            Schema::create('tenant_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event');
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->json('properties')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'event']);
            });
        }

        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('tenant_customers')->nullOnDelete();
                $table->string('recipient', 50);
                $table->text('message');
                $table->string('gateway')->default('default');
                $table->enum('status', ['sent', 'failed', 'pending'])->default('sent');
                $table->text('response')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        }

        if (!Schema::hasTable('security_audit_logs')) {
            Schema::create('security_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type');
                $table->enum('severity', ['info', 'warning', 'danger'])->default('info');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('description');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'event_type']);
            });
        }

        if (!Schema::hasTable('automation_logs')) {
            Schema::create('automation_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('task_name');
                $table->enum('status', ['success', 'failed', 'running'])->default('success');
                $table->double('execution_time_seconds')->default(0);
                $table->longText('output')->nullable();
                $table->longText('error')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('network_audit_logs')) {
            Schema::create('network_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('router_id')->nullable()->constrained('tenant_routers')->nullOnDelete();
                $table->foreignId('olt_id')->nullable()->constrained('tenant_olts')->nullOnDelete();
                $table->string('action');
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('success');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('network_audit_logs');
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('security_audit_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('tenant_activity_logs');
    }
};
