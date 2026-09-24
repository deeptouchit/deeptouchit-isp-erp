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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'btrc_license_no')) {
                $table->string('btrc_license_no')->nullable()->after('address');
            }
            if (!Schema::hasColumn('tenants', 'btrc_license_type')) {
                $table->string('btrc_license_type')->default('Nationwide ISP')->nullable()->after('btrc_license_no');
            }
            if (!Schema::hasColumn('tenants', 'trade_license_no')) {
                $table->string('trade_license_no')->nullable()->after('btrc_license_type');
            }
            if (!Schema::hasColumn('tenants', 'tin_bin_no')) {
                $table->string('tin_bin_no')->nullable()->after('trade_license_no');
            }
            if (!Schema::hasColumn('tenants', 'support_hotline')) {
                $table->string('support_hotline')->nullable()->after('tin_bin_no');
            }
            if (!Schema::hasColumn('tenants', 'billing_phone')) {
                $table->string('billing_phone')->nullable()->after('support_hotline');
            }
            if (!Schema::hasColumn('tenants', 'billing_email')) {
                $table->string('billing_email')->nullable()->after('billing_phone');
            }
            if (!Schema::hasColumn('tenants', 'website')) {
                $table->string('website')->nullable()->after('billing_email');
            }
            if (!Schema::hasColumn('tenants', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('website');
            }
            if (!Schema::hasColumn('tenants', 'contact_person_designation')) {
                $table->string('contact_person_designation')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('tenants', 'currency_code')) {
                $table->string('currency_code', 10)->default('BDT')->after('contact_person_designation');
            }
            if (!Schema::hasColumn('tenants', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->default('৳')->after('currency_code');
            }
            if (!Schema::hasColumn('tenants', 'timezone')) {
                $table->string('timezone', 50)->default('Asia/Dhaka')->after('currency_symbol');
            }
            if (!Schema::hasColumn('tenants', 'invoice_footer_notes')) {
                $table->text('invoice_footer_notes')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('tenants', 'favicon')) {
                $table->string('favicon')->nullable()->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'btrc_license_no',
                'btrc_license_type',
                'trade_license_no',
                'tin_bin_no',
                'support_hotline',
                'billing_phone',
                'billing_email',
                'website',
                'contact_person',
                'contact_person_designation',
                'currency_code',
                'currency_symbol',
                'timezone',
                'invoice_footer_notes',
                'favicon',
            ]);
        });
    }
};
