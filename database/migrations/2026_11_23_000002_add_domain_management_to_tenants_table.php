<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'custom_domain')) {
                $table->string('custom_domain')->nullable()->unique()->after('domain');
            }
            if (!Schema::hasColumn('tenants', 'domain_mode')) {
                $table->string('domain_mode', 20)->default('platform')->after('custom_domain');
            }
            if (!Schema::hasColumn('tenants', 'primary_domain')) {
                $table->string('primary_domain', 20)->default('platform')->after('domain_mode');
            }
            if (!Schema::hasColumn('tenants', 'domain_status')) {
                $table->string('domain_status', 20)->default('pending')->after('primary_domain');
            }
            if (!Schema::hasColumn('tenants', 'domain_verification_token')) {
                $table->string('domain_verification_token', 64)->nullable()->after('domain_status');
            }
            if (!Schema::hasColumn('tenants', 'domain_verified_at')) {
                $table->timestamp('domain_verified_at')->nullable()->after('domain_verification_token');
            }
            if (!Schema::hasColumn('tenants', 'ssl_status')) {
                $table->string('ssl_status', 20)->default('pending')->after('domain_verified_at');
            }
            if (!Schema::hasColumn('tenants', 'ssl_expires_at')) {
                $table->timestamp('ssl_expires_at')->nullable()->after('ssl_status');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropUnique(['custom_domain']);
                $table->dropColumn([
                    'custom_domain', 'domain_mode', 'primary_domain', 'domain_status',
                    'domain_verification_token', 'domain_verified_at', 'ssl_status', 'ssl_expires_at',
                ]);
            });
        }
    }
};
