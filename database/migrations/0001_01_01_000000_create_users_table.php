<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('reseller_id')->nullable()->index();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('mobile', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('role')->default('isp_admin');
                $table->string('admin_role', 60)->default('Super Administrator');
                $table->boolean('is_staff')->default(false);
                $table->string('staff_id', 30)->nullable();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->string('designation', 100)->nullable();
                $table->string('shift', 60)->default('Morning (08:00 - 16:00)');
                $table->enum('shift_status', ['on_duty', 'off_duty', 'on_leave'])->default('on_duty');
                $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
                $table->decimal('credit_balance', 10, 2)->default(0.00);
                $table->string('avatar')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->json('ip_allowlist')->nullable();
                $table->boolean('two_factor_enforced')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
