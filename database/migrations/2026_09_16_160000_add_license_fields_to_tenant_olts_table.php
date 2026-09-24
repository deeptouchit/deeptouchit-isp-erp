<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_olts', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_olts', 'license_limit')) {
                $table->unsignedTinyInteger('license_limit')->default(0)->after('temperature');
            }
            if (!Schema::hasColumn('tenant_olts', 'license_time_hours')) {
                $table->unsignedInteger('license_time_hours')->default(0)->after('license_limit');
            }
            if (!Schema::hasColumn('tenant_olts', 'license_auto_renew')) {
                $table->boolean('license_auto_renew')->default(false)->after('license_time_hours');
            }
            if (!Schema::hasColumn('tenant_olts', 'license_renew_threshold_hours')) {
                $table->unsignedInteger('license_renew_threshold_hours')->default(168)->after('license_auto_renew');
            }
            if (!Schema::hasColumn('tenant_olts', 'license_renew_days')) {
                $table->unsignedInteger('license_renew_days')->default(0)->after('license_renew_threshold_hours');
            }
            if (!Schema::hasColumn('tenant_olts', 'license_auth_password')) {
                $table->text('license_auth_password')->nullable()->after('license_renew_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_olts', function (Blueprint $table) {
            $table->dropColumn([
                'license_limit',
                'license_time_hours',
                'license_auto_renew',
                'license_renew_threshold_hours',
                'license_renew_days',
                'license_auth_password',
            ]);
        });
    }
};
