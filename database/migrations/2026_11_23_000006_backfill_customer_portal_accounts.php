<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenant_customers')->orderBy('id')->chunkById(500, function ($customers): void {
            foreach ($customers as $customer) {
                if (DB::table('tenant_customer_accounts')->where('customer_id', $customer->id)->exists()) {
                    continue;
                }

                $mobileDigits = preg_replace('/\D+/', '', (string) $customer->phone);
                if (strlen($mobileDigits) < 6 || empty($customer->customer_id)) {
                    continue;
                }

                $identifier = $customer->email ?: $customer->customer_id;
                if (DB::table('tenant_customer_accounts')
                    ->where('tenant_id', $customer->tenant_id)
                    ->where('login_identifier', $identifier)
                    ->exists()) {
                    $identifier = $customer->customer_id;
                }

                DB::table('tenant_customer_accounts')->insert([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'login_identifier' => $identifier,
                    'password' => Hash::make(substr($mobileDigits, -6)),
                    'status' => in_array($customer->status, ['disabled', 'archived'], true) ? 'disabled' : 'active',
                    'force_password_change' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Existing customer portal accounts must not be deleted during rollback.
    }
};
