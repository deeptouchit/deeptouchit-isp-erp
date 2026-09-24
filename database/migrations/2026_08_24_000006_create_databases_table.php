<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('db_user', 64);
            $table->string('db_password', 255);
            $table->string('host', 100)->default('localhost');
            $table->integer('port')->default(3306);
            $table->string('charset', 20)->default('utf8mb4');
            $table->string('collation', 50)->default('utf8mb4_unicode_ci');
            $table->bigInteger('size_bytes')->default(0);
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
