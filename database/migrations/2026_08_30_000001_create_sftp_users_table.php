<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sftp_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username', 50)->unique();
            $table->string('password', 255)->nullable();
            $table->enum('auth_type', ['password', 'key', 'both'])->default('password');
            $table->text('public_key')->nullable();
            $table->string('path', 255)->default('/var/www/vhosts');
            $table->string('shell', 100)->default('/usr/lib/openssh/sftp-server');
            $table->string('permissions', 20)->default('readwrite');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamp('last_connected_at')->nullable();
            $table->string('last_connected_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sftp_users');
    }
};
