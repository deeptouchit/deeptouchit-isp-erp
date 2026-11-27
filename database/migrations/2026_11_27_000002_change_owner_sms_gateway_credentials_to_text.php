<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('owner_sms_gateways')) {
            Schema::table('owner_sms_gateways', function (Blueprint $table) {
                $table->text('credentials')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('owner_sms_gateways')) {
            Schema::table('owner_sms_gateways', function (Blueprint $table) {
                $table->json('credentials')->nullable()->change();
            });
        }
    }
};
