<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false);
            $table->string('icon', 50)->default('ServerIcon');
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        Schema::create('dns_template_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dns_template_id')->constrained('dns_templates')->cascadeOnDelete();
            $table->string('name', 191)->default('@');
            $table->string('type', 20)->default('A');
            $table->text('content');
            $table->integer('ttl')->default(3600);
            $table->integer('priority')->nullable();
            $table->integer('port')->nullable();
            $table->integer('weight')->nullable();
            $table->timestamps();

            $table->index(['dns_template_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_template_records');
        Schema::dropIfExists('dns_templates');
    }
};
