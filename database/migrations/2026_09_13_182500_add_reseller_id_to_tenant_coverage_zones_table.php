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
        if (Schema::hasTable('tenant_coverage_zones') && !Schema::hasColumn('tenant_coverage_zones', 'reseller_id')) {
            Schema::table('tenant_coverage_zones', function (Blueprint $table) {
                $table->foreignId('reseller_id')->nullable()->after('tenant_id')->constrained('tenant_resellers')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tenant_coverage_zones') && Schema::hasColumn('tenant_coverage_zones', 'reseller_id')) {
            Schema::table('tenant_coverage_zones', function (Blueprint $table) {
                $table->dropForeign(['reseller_id']);
                $table->dropColumn('reseller_id');
            });
        }
    }
};
