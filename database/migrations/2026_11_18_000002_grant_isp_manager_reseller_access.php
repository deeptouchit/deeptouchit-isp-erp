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

        $permissionsToGrant = [
            'billing.wholesale',
            'resellers.view',
            'resellers.create',
            'resellers.edit',
            'resellers.wallet',
        ];

        DB::table('tenant_roles')
            ->where('name', 'isp_manager')
            ->where('is_system', true)
            ->get(['id', 'permissions'])
            ->each(function (object $role) use ($permissionsToGrant): void {
                $permissions = json_decode((string) $role->permissions, true);
                $permissions = is_array($permissions) ? $permissions : [];
                $permissions = array_values(array_unique(array_merge($permissions, $permissionsToGrant)));

                DB::table('tenant_roles')
                    ->where('id', $role->id)
                    ->update([
                        'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        // Existing manager roles may have customized reseller permissions.
    }
};
