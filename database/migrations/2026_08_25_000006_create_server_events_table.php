<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            
            $table->string('event_type', 50)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('message', 255);
            $table->json('metadata')->nullable();
            
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['server_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_events');
    }
};
