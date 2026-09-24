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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'reseller_id')) {
                    $table->foreignId('reseller_id')->nullable()->after('tenant_id')->constrained('tenant_resellers')->onDelete('set null');
                }
                if (!Schema::hasColumn('users', 'mobile')) {
                    $table->string('mobile', 50)->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'address')) {
                    $table->text('address')->nullable()->after('mobile');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'reseller_id')) {
                    $table->dropForeign(['reseller_id']);
                    $table->dropColumn('reseller_id');
                }
                if (Schema::hasColumn('users', 'mobile')) {
                    $table->dropColumn('mobile');
                }
                if (Schema::hasColumn('users', 'address')) {
                    $table->dropColumn('address');
                }
            });
        }
    }
};
