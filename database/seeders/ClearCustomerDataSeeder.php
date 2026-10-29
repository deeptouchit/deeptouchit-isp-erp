<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearCustomerDataSeeder extends Seeder
{
    /**
     * Clear all customer data and associated records cleanly.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        $tenantId = $tenant ? $tenant->id : 1;

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Delete associated payments
        if (Schema::hasTable('tenant_customer_payments')) {
            DB::table('tenant_customer_payments')->where('tenant_id', $tenantId)->delete();
            try {
                DB::statement('ALTER TABLE tenant_customer_payments AUTO_INCREMENT = 1;');
            } catch (\Throwable $e) {}
        }

        // 2. Delete associated invoices
        if (Schema::hasTable('tenant_customer_invoices')) {
            DB::table('tenant_customer_invoices')->where('tenant_id', $tenantId)->delete();
            try {
                DB::statement('ALTER TABLE tenant_customer_invoices AUTO_INCREMENT = 1;');
            } catch (\Throwable $e) {}
        }

        // 3. Nullify customer references in ONUs & Gateway transactions
        if (Schema::hasTable('tenant_onus') && Schema::hasColumn('tenant_onus', 'customer_id')) {
            DB::table('tenant_onus')->where('tenant_id', $tenantId)->update(['customer_id' => null]);
        }
        if (Schema::hasTable('tenant_gateway_transactions') && Schema::hasColumn('tenant_gateway_transactions', 'customer_id')) {
            DB::table('tenant_gateway_transactions')->where('tenant_id', $tenantId)->update(['customer_id' => null]);
        }

        // 4. Delete all customer records
        DB::table('tenant_customers')->where('tenant_id', $tenantId)->delete();
        try {
            DB::statement('ALTER TABLE tenant_customers AUTO_INCREMENT = 1;');
        } catch (\Throwable $e) {}

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
