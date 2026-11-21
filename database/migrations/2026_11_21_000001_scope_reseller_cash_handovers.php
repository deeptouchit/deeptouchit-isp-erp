<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_daily_cash_handovers', function (Blueprint $table) {
            $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->restrictOnDelete();
            $table->index(['tenant_id', 'reseller_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_daily_cash_handovers', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'reseller_id']);
            $table->dropConstrainedForeignId('reseller_id');
        });
    }
};
