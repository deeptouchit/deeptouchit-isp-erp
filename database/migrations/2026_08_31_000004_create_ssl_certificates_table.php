<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('domain', 191);
            $table->json('san_domains')->nullable();
            $table->string('issuer', 191)->default("Let's Encrypt Authority");
            $table->string('type', 30)->default('letsencrypt'); // letsencrypt, custom, self_signed
            $table->longText('certificate');
            $table->longText('private_key');
            $table->longText('ca_bundle')->nullable();
            $table->string('cert_path', 255)->nullable();
            $table->string('key_path', 255)->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('force_https')->default(true);
            $table->boolean('hsts_enabled')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['domain', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
