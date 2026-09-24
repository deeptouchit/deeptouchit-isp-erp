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
        if (Schema::hasTable('support_tickets') && !Schema::hasColumn('support_tickets', 'reseller_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('reseller_id')->nullable()->after('tenant_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'reseller_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropColumn('reseller_id');
            });
        }
    }
};
