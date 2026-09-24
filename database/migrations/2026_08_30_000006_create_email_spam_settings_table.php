<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_spam_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_domain_id')->nullable()->constrained('email_domains')->cascadeOnDelete();
            $table->decimal('required_score', 4, 1)->default(5.0);
            $table->boolean('rewrite_subject')->default(true);
            $table->string('subject_tag', 64)->default('***SPAM***');
            $table->decimal('auto_delete_score', 4, 1)->nullable()->default(15.0);
            $table->boolean('is_auto_delete_enabled')->default(false);
            $table->text('whitelist')->nullable();
            $table->text('blacklist')->nullable();
            $table->boolean('bayesian_filter_enabled')->default(true);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_spam_settings');
    }
};
