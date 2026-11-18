<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                DB::table('users')
                    ->where('tenant_id', $reseller->tenant_id)
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $reseller->email))])
                    ->update([
                        'reseller_id' => $reseller->id,
                        'role' => 'reseller_admin',
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // Partner portal links may have been customized after creation.
    }
};
