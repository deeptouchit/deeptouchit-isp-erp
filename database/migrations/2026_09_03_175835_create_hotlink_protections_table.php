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
        Schema::create('hotlink_protections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain');
            $table->boolean('is_enabled')->default(true);
            $table->text('allowed_extensions'); // jpg,jpeg,png,gif,webp,svg,mp4,mp3,pdf,zip,avif
            $table->text('allowed_referrers')->nullable(); // newline or comma separated domains
            $table->boolean('allow_direct_requests')->default(true);
            $table->string('redirect_url')->nullable(); // custom image or empty for 403 Forbidden
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotlink_protections');
    }
};
