<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_forwarders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('email_domain_id')->constrained('email_domains')->cascadeOnDelete();
            $table->string('source', 191);
            $table->text('destination');
            $table->boolean('keep_local_copy')->default(false);
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->timestamps();

            $table->unique(['email_domain_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_forwarders');
    }
};
