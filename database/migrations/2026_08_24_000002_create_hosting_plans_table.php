<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->integer('disk_space')->comment('MB');
            $table->integer('bandwidth')->comment('MB');
            $table->integer('max_domains')->default(1);
            $table->integer('max_subdomains')->default(5);
            $table->integer('max_databases')->default(1);
            $table->integer('max_email_accounts')->default(5);
            $table->integer('max_ftp_accounts')->default(1);
            $table->integer('cpu_limit')->default(50)->comment('Percentage');
            $table->integer('ram_limit')->default(512)->comment('MB');
            $table->string('php_version_default', 10)->default('8.2');
            $table->boolean('allow_custom_php_ini')->default(false);
            $table->boolean('allow_ssh_access')->default(false);
            $table->boolean('allow_git_deploy')->default(false);
            $table->boolean('auto_ssl')->default(true);
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0.00);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_plans');
    }
};
