<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('domain_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('website_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_domain')->default('all');
            $table->string('source_path')->default('/');
            $table->text('target_url');
            $table->unsignedSmallInteger('redirect_code')->default(301); // 301 Permanent, 302 Temporary, 307, 308
            $table->string('www_redirect_type')->default('with_or_without'); // with_or_without, only_with, do_not_redirect
            $table->boolean('match_wildcard')->default(false);
            $table->string('status')->default('active'); // active, disabled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_redirects');
    }
};
