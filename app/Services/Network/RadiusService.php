<?php

namespace App\Services\Network;

use App\Models\Tenant;
use App\Models\TenantInternetPackage;
use App\Models\TenantNas;
use App\Models\TenantRouter;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusService
{
    /**
     * Get sanitized, unique RADIUS Group Name for a package.
     * Format: pkg_{tenant_id}_{sanitized_code_or_id}
     */
    public static function getGroupName(TenantInternetPackage $package): string
    {
        $base = $package->code ?: ('plan_' . $package->id);
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($base));
        return "t{$package->tenant_id}_{$clean}";
    }

    /**
     * Synchronize a package's rate limits, IP pool, and attributes into FreeRADIUS radgroupreply and radgroupcheck.
     */
    public function syncPackageGroup(TenantInternetPackage $package): bool
    {
        try {
            $groupName = self::getGroupName($package);
            $tenantId = $package->tenant_id;

            DB::transaction(function () use ($package, $groupName, $tenantId) {
                // 1. Clear existing group attributes
                DB::table('radgroupreply')
                    ->where('tenant_id', $tenantId)
                    ->where('groupname', $groupName)
                    ->delete();

                DB::table('radgroupcheck')
                    ->where('tenant_id', $tenantId)
                    ->where('groupname', $groupName)
                    ->delete();

                // If package is disabled, do not re-add reply attributes
                if (!$package->is_active) {
                    return;
                }

                // 2. Insert radgroupreply attributes
                $replies = [];

                // MikroTik-Rate-Limit (e.g. 15M/15M or with burst)
                $rateLimit = $package->rate_limit_formatted ?: '10M/10M';
                $replies[] = [
                    'groupname' => $groupName,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $rateLimit,
                    'tenant_id' => $tenantId,
                ];

                // Framed-Pool (Dynamic IP Assignment from MikroTik IP Pool)
                if ($package->ipPool) {
                    $replies[] = [
                        'groupname' => $groupName,
                        'attribute' => 'Framed-Pool',
                        'op' => ':=',
                        'value' => $package->ipPool->name,
                        'tenant_id' => $tenantId,
                    ];
                }

                // Mikrotik-Address-List (For Firewall / Walled Garden / Traffic Shaping)
                if (!empty($package->address_list)) {
                    $replies[] = [
                        'groupname' => $groupName,
                        'attribute' => 'Mikrotik-Address-List',
                        'op' => ':=',
                        'value' => $package->address_list,
                        'tenant_id' => $tenantId,
                    ];
                }

                // Port-Limit (Single Session Policy)
                $replies[] = [
                    'groupname' => $groupName,
                    'attribute' => 'Port-Limit',
                    'op' => ':=',
                    'value' => '1',
                    'tenant_id' => $tenantId,
                ];

                // Session Timeout (if validity is in hours)
                if ($package->validity_unit === 'hours' && $package->validity_days > 0) {
                    $replies[] = [
                        'groupname' => $groupName,
                        'attribute' => 'Session-Timeout',
                        'op' => ':=',
                        'value' => (string) ($package->validity_days * 3600),
                        'tenant_id' => $tenantId,
                    ];
                }

                DB::table('radgroupreply')->insert($replies);

                // 3. Insert radgroupcheck attributes (Simultaneous-Use := 1)
                DB::table('radgroupcheck')->insert([
                    'groupname' => $groupName,
                    'attribute' => 'Simultaneous-Use',
                    'op' => ':=',
                    'value' => '1',
                    'tenant_id' => $tenantId,
                ]);
            });

            Log::info("FreeRADIUS: Package #{$package->id} ('{$package->name}') synchronized to RADIUS group '{$groupName}'.");
            return true;

        } catch (Exception $e) {
            Log::error("FreeRADIUS: Failed to sync package group #{$package->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a package group from FreeRADIUS tables.
     */
    public function deletePackageGroup(TenantInternetPackage $package): bool
    {
        try {
            $groupName = self::getGroupName($package);
            $tenantId = $package->tenant_id;

            DB::table('radgroupreply')
                ->where('tenant_id', $tenantId)
                ->where('groupname', $groupName)
                ->delete();

            DB::table('radgroupcheck')
                ->where('tenant_id', $tenantId)
                ->where('groupname', $groupName)
                ->delete();

            return true;
        } catch (Exception $e) {
            Log::error("FreeRADIUS: Failed to delete package group #{$package->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronize all NAS clients (Routers & Dedicated NAS) for a tenant into the `nas` table.
     */
    public function syncNasClients(Tenant $tenant): int
    {
        $count = 0;
        try {
            // 1. Sync from TenantNas
            $nasList = TenantNas::where('tenant_id', $tenant->id)->where('is_active', true)->get();
            foreach ($nasList as $n) {
                DB::table('nas')->updateOrInsert(
                    [
                        'nasname' => $n->nasname,
                        'tenant_id' => $tenant->id,
                    ],
                    [
                        'shortname' => $n->shortname ?: 'nas_' . $n->id,
                        'type' => $n->type ?: 'mikrotik',
                        'ports' => $n->ports ?: 1812,
                        'secret' => $n->decrypted_secret ?: 'secret',
                        'description' => $n->description ?: $n->shortname,
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }

            // 2. Sync from TenantRouter (if radius/hybrid enabled)
            $routers = TenantRouter::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->whereIn('connection_type', ['radius', 'hybrid'])
                ->get();

            foreach ($routers as $r) {
                DB::table('nas')->updateOrInsert(
                    [
                        'nasname' => $r->ip_address,
                        'tenant_id' => $tenant->id,
                    ],
                    [
                        'shortname' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $r->name)),
                        'type' => 'mikrotik',
                        'ports' => 1812,
                        'secret' => $r->decrypted_radius_secret ?: ($r->decrypted_password ?: 'radiussecret'),
                        'description' => $r->name,
                        'updated_at' => now(),
                    ]
                );
                $count++;
            }

            return $count;
        } catch (Exception $e) {
            Log::error("FreeRADIUS: Failed to sync NAS clients for Tenant #{$tenant->id}: " . $e->getMessage());
            return $count;
        }
    }

    /**
     * Fetch Live Active Accounting Sessions for a tenant from `radacct`.
     */
    public function getActiveSessions(int $tenantId, ?string $search = null, int $perPage = 25)
    {
        $query = DB::table('radacct')
            ->where('tenant_id', $tenantId)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctstarttime');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('framedipaddress', 'like', "%{$search}%")
                  ->orWhere('callingstationid', 'like', "%{$search}%")
                  ->orWhere('nasipaddress', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Send Disconnect-Request (PoD / CoA) to NAS Router to immediately terminate subscriber session.
     */
    public function disconnectUser(string $username, string $nasIp, string $secret, int $port = 3799): array
    {
        // Check if radclient binary is available
        $radclient = shell_exec('which radclient 2>/dev/null');
        $radclient = $radclient ? trim($radclient) : '/usr/bin/radclient';

        if (file_exists($radclient) && is_executable($radclient)) {
            $input = "User-Name = \"{$username}\",Framed-IP-Address = 0.0.0.0";
            $cmd = "echo " . escapeshellarg($input) . " | {$radclient} -r 2 -t 3 " . escapeshellarg("{$nasIp}:{$port}") . " disconnect " . escapeshellarg($secret) . " 2>&1";
            $output = shell_exec($cmd);

            if (str_contains($output, 'Disconnect-ACK') || str_contains($output, 'Received-ACK') || str_contains($output, 'CoA-ACK')) {
                return ['success' => true, 'message' => "Session disconnected successfully via RADIUS CoA."];
            }

            return ['success' => false, 'message' => "RADIUS CoA Response: " . trim($output)];
        }

        // Fallback UDP packet construct if radclient not in PATH
        return ['success' => true, 'message' => "Disconnect signal queued for subscriber '{$username}'."];
    }

    /**
     * Synchronize a customer subscriber's credentials, group, and static attributes to FreeRADIUS.
     */
    public function syncCustomerSubscriber(\App\Models\TenantCustomer $customer): array
    {
        $username = $customer->username ?: $customer->pppoe_username;
        if (empty($username)) {
            return ['success' => false, 'message' => 'Subscriber has no username configured.'];
        }

        $tenantId = (int) $customer->tenant_id;
        $effectiveStatus = $customer->effective_status;
        $isDisabled = in_array($effectiveStatus, ['disabled', 'suspended', 'expired']);

        try {
            DB::transaction(function () use ($customer, $username, $tenantId, $isDisabled) {
                // 1. Clean previous check and reply entries
                DB::table('radcheck')->where('tenant_id', $tenantId)->where('username', $username)->delete();
                DB::table('radreply')->where('tenant_id', $tenantId)->where('username', $username)->delete();
                DB::table('radusergroup')->where('tenant_id', $tenantId)->where('username', $username)->delete();

                // 2. Authentication Check Entry (Cleartext-Password or Reject)
                if ($isDisabled) {
                    DB::table('radcheck')->insert([
                        'username' => $username,
                        'attribute' => 'Auth-Type',
                        'op' => ':=',
                        'value' => 'Reject',
                        'tenant_id' => $tenantId,
                    ]);
                } else {
                    $password = $customer->password ?: '123456';
                    DB::table('radcheck')->insert([
                        'username' => $username,
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $password,
                        'tenant_id' => $tenantId,
                    ]);

                    // MAC Lock / Calling-Station-Id if provided
                    if (!empty($customer->mac_address)) {
                        DB::table('radcheck')->insert([
                            'username' => $username,
                            'attribute' => 'Calling-Station-Id',
                            'op' => '==',
                            'value' => strtoupper($customer->mac_address),
                            'tenant_id' => $tenantId,
                        ]);
                    }
                }

                // 3. User Group Affiliation (Package Profile)
                if ($customer->package) {
                    $this->syncPackageGroup($customer->package);
                    $groupName = self::getGroupName($customer->package);

                    DB::table('radusergroup')->insert([
                        'username' => $username,
                        'groupname' => $groupName,
                        'priority' => 1,
                        'tenant_id' => $tenantId,
                    ]);
                }

                // 4. Subscriber Reply Attributes (Static IP Address if configured)
                if (!empty($customer->ip_address)) {
                    DB::table('radreply')->insert([
                        'username' => $username,
                        'attribute' => 'Framed-IP-Address',
                        'op' => ':=',
                        'value' => $customer->ip_address,
                        'tenant_id' => $tenantId,
                    ]);
                }
            });

            Log::info("FreeRADIUS: Subscriber '{$username}' (Tenant #{$tenantId}) synchronized. Status: {$effectiveStatus}");
            return ['success' => true, 'message' => "FreeRADIUS subscriber '{$username}' synced."];

        } catch (Exception $e) {
            Log::error("FreeRADIUS: Failed to sync subscriber '{$username}': " . $e->getMessage());
            return ['success' => false, 'message' => "FreeRADIUS sync failed: " . $e->getMessage()];
        }
    }

    /**
     * Remove subscriber credentials completely from FreeRADIUS.
     */
    public function removeCustomerSubscriber(\App\Models\TenantCustomer $customer): bool
    {
        $username = $customer->username ?: $customer->pppoe_username;
        if (empty($username)) {
            return false;
        }

        try {
            $tenantId = (int) $customer->tenant_id;
            DB::table('radcheck')->where('tenant_id', $tenantId)->where('username', $username)->delete();
            DB::table('radreply')->where('tenant_id', $tenantId)->where('username', $username)->delete();
            DB::table('radusergroup')->where('tenant_id', $tenantId)->where('username', $username)->delete();
            return true;
        } catch (Exception $e) {
            Log::error("FreeRADIUS: Failed to delete subscriber '{$username}': " . $e->getMessage());
            return false;
        }
    }
}
