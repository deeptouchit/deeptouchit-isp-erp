<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_auto_responders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('email_domain_id')->constrained('email_domains')->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->string('email', 191);
            $table->string('from_name', 191)->nullable();
            $table->string('subject', 191);
            $table->text('body');
            $table->boolean('is_html')->default(false);
            $table->integer('interval_hours')->default(24);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'paused', 'expired'])->default('active');
            $table->timestamps();

            $table->unique(['email_domain_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_auto_responders');
    }
};
