<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_accounts') && !Schema::hasColumn('email_accounts', 'used_quota_mb')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->integer('used_quota_mb')->default(0)->after('quota_mb');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_accounts') && Schema::hasColumn('email_accounts', 'used_quota_mb')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->dropColumn('used_quota_mb');
            });
        }
    }
};
