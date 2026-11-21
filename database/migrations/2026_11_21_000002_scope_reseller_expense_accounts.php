<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tenant_expense_categories', 'tenant_expense_transactions'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('reseller_id')->nullable()->constrained('tenant_resellers')->restrictOnDelete();
                $table->index(['tenant_id', 'reseller_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['tenant_expense_transactions', 'tenant_expense_categories'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'reseller_id']);
                $table->dropConstrainedForeignId('reseller_id');
            });
        }
    }
};
