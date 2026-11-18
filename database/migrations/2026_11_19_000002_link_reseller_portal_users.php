<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_resellers') || !Schema::hasTable('users')) {
            return;
        }

        DB::table('tenant_resellers')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'email'])
            ->each(function (object $reseller): void {
                $email = strtolower(trim((string) $reseller->email));
                $portalUser = DB::table('users')
                    ->where('tenant_id', $reseller->tenant_id)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->first();

                if ($portalUser) {
                    DB::table('users')
                        ->where('id', $portalUser->id)
                        ->update([
                            'reseller_id' => $reseller->id,
                            'role' => 'reseller_admin',
                            'updated_at' => now(),
                        ]);
                    return;
                }

                DB::table('users')->insert([
                    'tenant_id' => $reseller->tenant_id,
                    'reseller_id' => $reseller->id,
                    'name' => 'Reseller Portal Admin',
                    'email' => $email,
                    'password' => Hash::make('12345678'),
                    'role' => 'reseller_admin',
                    'status' => 'active',
                    'remember_token' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Partner portal links may have been customized after creation.
    }
};
