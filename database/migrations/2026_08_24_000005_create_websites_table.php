<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('domain', 255);
            $table->string('subdomain', 255)->nullable();
            $table->string('document_root', 255);
            $table->string('php_version', 10)->default('8.2');
            $table->enum('ssl_status', ['none', 'active', 'expiring'])->default('none');
            $table->timestamp('ssl_expires_at')->nullable();
            $table->timestamp('ssl_last_renewed_at')->nullable();
            $table->boolean('auto_ssl')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('domain', 'idx_websites_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
