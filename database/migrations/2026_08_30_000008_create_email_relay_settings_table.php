<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_relay_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('mode', 50)->default('direct'); // 'direct' | 'relay'
            $table->string('provider', 50)->default('custom'); // 'custom', 'brevo', 'sendgrid', 'mailgun', 'ses', 'gmail'
            $table->string('host', 191)->nullable();
            $table->integer('port')->default(587);
            $table->string('encryption', 20)->default('tls'); // 'tls', 'ssl', 'none'
            $table->string('username', 191)->nullable();
            $table->text('password')->nullable();
            $table->string('sender_domain', 191)->nullable();
            $table->timestamp('last_test_at')->nullable();
            $table->string('last_test_status', 50)->nullable();
            $table->text('last_test_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_relay_settings');
    }
};
