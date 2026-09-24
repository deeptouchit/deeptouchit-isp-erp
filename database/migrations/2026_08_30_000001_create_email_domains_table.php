<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('domain', 191)->unique();
            $table->enum('status', ['active', 'suspended', 'disabled'])->default('active');
            $table->boolean('is_catchall_enabled')->default(false);
            $table->string('catchall_destination', 191)->nullable();
            $table->enum('dkim_status', ['active', 'not_generated', 'error'])->default('not_generated');
            $table->string('dkim_selector', 64)->default('default');
            $table->text('dkim_private_key')->nullable();
            $table->text('dkim_public_key')->nullable();
            $table->string('spf_record', 255)->nullable();
            $table->string('dmarc_record', 255)->nullable();
            $table->integer('max_accounts')->default(50);
            $table->integer('max_quota_mb')->default(10240);
            $table->timestamps();
        });

        // Add email_domain_id to email_accounts if not present
        if (Schema::hasTable('email_accounts') && !Schema::hasColumn('email_accounts', 'email_domain_id')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->foreignId('email_domain_id')->nullable()->after('subscription_id')->constrained('email_domains')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_accounts') && Schema::hasColumn('email_accounts', 'email_domain_id')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->dropForeign(['email_domain_id']);
                $table->dropColumn('email_domain_id');
            });
        }
        Schema::dropIfExists('email_domains');
    }
};
