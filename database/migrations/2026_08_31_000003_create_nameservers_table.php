<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nameservers', function (Blueprint $table) {
            $table->id();
            $table->string('hostname', 191)->unique();
            $table->string('ip_address', 50);
            $table->string('ipv6_address', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamp('last_checked_at')->nullable();
            $table->string('check_status', 30)->default('online');
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nameservers');
    }
};
