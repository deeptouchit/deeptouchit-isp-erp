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
        Schema::create('git_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('repository_url');
            $table->string('branch')->default('main');
            $table->string('deploy_path');
            $table->string('provider')->default('github'); // github, gitlab, bitbucket, custom
            $table->string('webhook_secret')->nullable();
            $table->text('post_deploy_script')->nullable(); // e.g. composer install, php artisan migrate
            $table->boolean('auto_deploy')->default(true);
            $table->string('status')->default('active'); // active, cloning, failed
            $table->string('last_commit_hash')->nullable();
            $table->string('last_commit_message')->nullable();
            $table->string('last_commit_author')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->string('last_deployment_status')->nullable(); // success, failed
            $table->timestamps();
        });

        Schema::create('git_deployment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_repository_id')->constrained()->cascadeOnDelete();
            $table->string('trigger_type')->default('manual'); // manual, webhook, rollback
            $table->string('commit_hash')->nullable();
            $table->string('commit_message')->nullable();
            $table->string('commit_author')->nullable();
            $table->string('status')->default('success'); // success, failed
            $table->integer('duration_ms')->default(0);
            $table->integer('exit_code')->default(0);
            $table->longText('output')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_deployment_logs');
        Schema::dropIfExists('git_repositories');
    }
};
