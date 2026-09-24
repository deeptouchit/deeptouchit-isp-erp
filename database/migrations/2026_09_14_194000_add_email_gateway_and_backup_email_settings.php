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
            if (!Schema::hasColumn('tenants', 'mail_driver')) {
                $table->string('mail_driver')->default('smtp')->after('billing_email');
                $table->string('mail_host')->nullable()->after('mail_driver');
                $table->integer('mail_port')->default(587)->after('mail_host');
                $table->string('mail_username')->nullable()->after('mail_port');
                $table->text('mail_password')->nullable()->after('mail_username');
                $table->string('mail_encryption')->default('tls')->after('mail_password');
                $table->string('mail_from_address')->nullable()->after('mail_encryption');
                $table->string('mail_from_name')->nullable()->after('mail_from_address');
                $table->boolean('mail_enabled')->default(false)->after('mail_from_name');
            }
        });

        Schema::table('tenant_automation_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_automation_settings', 'auto_backup_email_enabled')) {
                $table->boolean('auto_backup_email_enabled')->default(false)->after('backup_retention_days');
                $table->string('backup_destination_email')->nullable()->after('auto_backup_email_enabled');
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
                'mail_driver',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
                'mail_enabled',
            ]);
        });

        Schema::table('tenant_automation_settings', function (Blueprint $table) {
            $table->dropColumn([
                'auto_backup_email_enabled',
                'backup_destination_email',
            ]);
        });
    }
};
