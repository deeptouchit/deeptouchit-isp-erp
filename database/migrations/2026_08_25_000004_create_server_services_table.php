<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            
            $table->string('service_name', 100);
            $table->string('display_name', 100);
            $table->string('service_type', 50)->default('system');
            
            $table->string('status', 30)->default('unknown');
            $table->string('version', 50)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('pid')->nullable();
            
            $table->decimal('cpu_usage', 5, 2)->default(0);
            $table->unsignedBigInteger('memory_usage')->default(0)->comment('MB');
            
            $table->timestamp('last_checked_at')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->unique(['server_id', 'service_name']);
            $table->index(['server_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_services');
    }
};
