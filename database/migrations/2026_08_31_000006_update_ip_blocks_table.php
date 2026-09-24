<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_blocks', function (Blueprint $table) {
            if (!Schema::hasColumn('ip_blocks', 'status')) {
                $table->string('status', 30)->default('active')->after('expires_at');
            }
            if (!Schema::hasColumn('ip_blocks', 'is_subnet')) {
                $table->boolean('is_subnet')->default(false)->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ip_blocks', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_subnet']);
        });
    }
};
