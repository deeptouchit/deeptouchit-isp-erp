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
        Schema::create('protected_directories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path'); // e.g. /admin, /wp-admin, /staging, /docs
            $table->string('realm')->default('Protected Area'); // HTTP Auth Prompt realm
            $table->string('domain')->nullable(); // Target domain scope
            $table->boolean('is_active')->default(true);
            $table->string('htpasswd_file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('protected_directory_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protected_directory_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->string('password_hash'); // crypt / apr1 / bcrypt hash for .htpasswd
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protected_directory_users');
        Schema::dropIfExists('protected_directories');
    }
};
