<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_roles')) {
            return;
        }

        $permissions = [
            'customers.view', 'customers.create', 'customers.edit', 'customers.status_toggle', 'customers.export',
            'staff.view',
            'billing.invoices', 'billing.collect', 'billing.cash_handover', 'billing.expenses', 'billing.ledger',
            'support.tickets', 'support.field_jobs', 'support.installations', 'support.escalations', 'support.sla',
            'reports.revenue', 'reports.collection', 'reports.inventory',
        ];

        DB::table('tenant_roles')
            ->where('name', 'isp_manager')
            ->where('is_system', true)
            ->update([
                'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Previous manager grants may have been customized per tenant.
    }
};
