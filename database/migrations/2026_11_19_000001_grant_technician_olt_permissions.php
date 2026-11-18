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

        $roles = ['isp_noc', 'isp_technician', 'technician', 'isp_lineman'];
        $permissionsToGrant = [
            'customers.view',
            'network.olts_onus',
            'support.tickets',
            'support.field_jobs',
            'support.installations',
        ];

        DB::table('tenant_roles')
            ->whereIn('name', $roles)
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
        // Preserve customized tenant role permissions.
    }
};
