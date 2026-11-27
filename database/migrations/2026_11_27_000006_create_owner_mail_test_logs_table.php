<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_mail_test_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient');
            $table->string('host');
            $table->unsignedSmallInteger('port');
            $table->string('encryption', 20);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('success')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('tested_at');
            $table->timestamps();
            $table->index(['success', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_mail_test_logs');
    }
};
