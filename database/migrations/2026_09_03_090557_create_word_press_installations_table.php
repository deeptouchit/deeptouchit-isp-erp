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
        Schema::create('wordpress_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('website_id')->nullable()->constrained()->onDelete('set null');
            $table->string('site_title')->default('WordPress Website');
            $table->string('domain');
            $table->string('install_path')->default('/');
            $table->string('version')->default('6.7.1');
            $table->string('admin_username')->default('admin');
            $table->string('admin_email');
            $table->string('db_name');
            $table->string('db_user');
            $table->string('db_prefix')->default('wp_');
            $table->string('php_version')->default('8.2');
            $table->boolean('ssl_enabled')->default(true);
            $table->boolean('auto_update_core')->default(true);
            $table->boolean('auto_update_plugins')->default(true);
            $table->boolean('auto_update_themes')->default(false);
            $table->boolean('maintenance_mode')->default(false);
            $table->string('status')->default('active'); // active, updating, staging
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress_installations');
    }
};
