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
        Schema::table('websites', function (Blueprint $table) {
            $table->boolean('cdn_enabled')->default(true)->after('status');
            $table->boolean('cdn_dev_mode')->default(false)->after('cdn_enabled');
            $table->boolean('cdn_always_online')->default(true)->after('cdn_dev_mode');
            $table->boolean('cdn_brotli')->default(true)->after('cdn_always_online');
            $table->boolean('cdn_waf_enabled')->default(true)->after('cdn_brotli');
            $table->string('cdn_cache_level')->default('standard')->after('cdn_waf_enabled'); // standard, aggressive, bypass
            $table->unsignedInteger('cdn_browser_ttl')->default(14400)->after('cdn_cache_level'); // in seconds (4h, 1d, 1m, 1y)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn([
                'cdn_enabled',
                'cdn_dev_mode',
                'cdn_always_online',
                'cdn_brotli',
                'cdn_waf_enabled',
                'cdn_cache_level',
                'cdn_browser_ttl'
            ]);
        });
    }
};
