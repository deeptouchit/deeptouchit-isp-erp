<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            
            $table->string('log_type', 30)->default('system');
            $table->string('level', 20)->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['server_id', 'occurred_at']);
            $table->index(['server_id', 'log_type']);
            $table->index(['server_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_logs');
    }
};
