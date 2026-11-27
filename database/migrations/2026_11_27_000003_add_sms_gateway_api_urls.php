<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_sms_gateways', function (Blueprint $table) {
            $table->string('sms_api_url', 1000)->nullable()->after('custom_url');
            $table->string('balance_api_url', 1000)->nullable()->after('sms_api_url');
        });
    }

    public function down(): void
    {
        Schema::table('owner_sms_gateways', function (Blueprint $table) {
            $table->dropColumn(['sms_api_url', 'balance_api_url']);
        });
    }
};
