<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_accounts')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->foreignId('subscription_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('email_accounts')) {
            Schema::table('email_accounts', function (Blueprint $table) {
                $table->foreignId('subscription_id')->nullable(false)->change();
            });
        }
    }
};
