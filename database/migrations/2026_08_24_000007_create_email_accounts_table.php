<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->cascadeOnDelete();
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->integer('quota_mb')->default(1024);
            $table->string('forward_to', 255)->nullable();
            $table->text('auto_responder')->nullable();
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
