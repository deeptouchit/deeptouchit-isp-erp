<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantOnu;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoLinkCustomersOnu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:auto-link-onus {--tenant= : Optional tenant ID} {--force : Force re-evaluate already linked customers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically match and link Customers with OLT ONUs using PPPoE Router MAC, Caller-ID, and Usernames';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $force = (bool) $this->option('force');

        $tenantQuery = Tenant::query();
        if ($tenantId) {
            $tenantQuery->where('id', $tenantId);
        }
        $tenants = $tenantQuery->get();

        foreach ($tenants as $tenant) {
            $this->info("Processing Tenant: {$tenant->name} (ID: {$tenant->id})...");

            $onus = TenantOnu::where('tenant_id', $tenant->id)->get();
            $this->info("Found {$onus->count()} ONUs across all OLTs.");

            if ($onus->isEmpty()) {
                continue;
            }

            // Index ONUs by normalized MAC and router_mac
            $onuByCleanMac = [];
            $onuByCleanRouterMac = [];
            $onuByUsername = [];

            foreach ($onus as $onu) {
                if (!empty($onu->mac_address)) {
                    $cMac = strtolower(preg_replace('/[^a-f0-9]/i', '', $onu->mac_address));
                    if (strlen($cMac) >= 10) {
                        $onuByCleanMac[$cMac] = $onu;
                    }
                }

                if (!empty($onu->router_mac)) {
                    $cRMac = strtolower(preg_replace('/[^a-f0-9]/i', '', $onu->router_mac));
                    if (strlen($cRMac) >= 10) {
                        $onuByCleanRouterMac[$cRMac] = $onu;
                    }
                }

                if (!empty($onu->pppoe_username)) {
                    $onuByUsername[strtolower(trim($onu->pppoe_username))] = $onu;
                }

                if (!empty($onu->name)) {
                    $onuByUsername[strtolower(trim($onu->name))] = $onu;
                }

                if (!empty($onu->desc)) {
                    $onuByUsername[strtolower(trim($onu->desc))] = $onu;
                }
            }

            // Fetch customers
            $customers = TenantCustomer::where('tenant_id', $tenant->id)->get();
            $this->info("Evaluating {$customers->count()} Customers for automatic pairing...");

            $linkedCount = 0;

            foreach ($customers as $c) {
                // If already linked and not force mode, skip
                if (!$force && !empty($c->onu_mac_sn) && !empty($c->olt_id) && $c->onu_device !== null) {
                    continue;
                }

                $matchedOnu = null;

                // Strategy 1: Check Customer's Router MAC
                $routerMac = $c->mac_address;
                if (empty($routerMac) && !empty($c->username)) {
                    try {
                        $sess = DB::table('radacct')
                            ->where('username', $c->username)
                            ->whereNotNull('callingstationid')
                            ->where('callingstationid', '!=', '')
                            ->orderByDesc('radacctid')
                            ->first();
                        if ($sess && !empty($sess->callingstationid)) {
                            $routerMac = $sess->callingstationid;
                            $c->updateQuietly(['mac_address' => $routerMac]);
                        }
                    } catch (\Throwable $e) {
                        // Silent
                    }
                }

                if (!empty($routerMac)) {
                    $cClean = strtolower(preg_replace('/[^a-f0-9]/i', '', $routerMac));
                    if (strlen($cClean) >= 10) {
                        $matchedOnu = $onuByCleanRouterMac[$cClean] ?? ($onuByCleanMac[$cClean] ?? null);
                    }
                }

                // Strategy 2: Check Customer's onu_mac_sn
                if (!$matchedOnu && !empty($c->onu_mac_sn)) {
                    $cOnuClean = strtolower(preg_replace('/[^a-f0-9]/i', '', $c->onu_mac_sn));
                    if (strlen($cOnuClean) >= 10) {
                        $matchedOnu = $onuByCleanMac[$cOnuClean] ?? null;
                    }
                }

                // Strategy 3: Exact Username or Customer ID
                if (!$matchedOnu && !empty($c->username)) {
                    $uKey = strtolower(trim($c->username));
                    $matchedOnu = $onuByUsername[$uKey] ?? null;
                }

                if (!$matchedOnu && !empty($c->customer_id)) {
                    $cidKey = strtolower(trim($c->customer_id));
                    $matchedOnu = $onuByUsername[$cidKey] ?? null;
                }

                // Apply link
                if ($matchedOnu) {
                    $matchedOnu->updateQuietly([
                        'customer_id' => $c->id,
                        'router_mac' => $routerMac ?: ($matchedOnu->router_mac ?: $c->mac_address),
                        'pppoe_username' => $c->username ?: $matchedOnu->pppoe_username,
                    ]);

                    $c->updateQuietly([
                        'olt_id' => $matchedOnu->olt_id,
                        'pon_port' => $matchedOnu->pon_port . ($matchedOnu->onu_id ? ':' . $matchedOnu->onu_id : ''),
                        'onu_mac_sn' => $matchedOnu->mac_address ?: $c->onu_mac_sn,
                    ]);

                    $linkedCount++;
                    $this->line("  [MATCHED] {$c->name} ({$c->username}) <==> OLT #{$matchedOnu->olt_id} ({$matchedOnu->pon_port}:{$matchedOnu->onu_id}) ONU: '{$matchedOnu->name}' MAC: {$matchedOnu->mac_address} RMAC: {$matchedOnu->router_mac}");
                }
            }

            $this->info("Completed. Successfully linked {$linkedCount} customers with their OLT ONUs.");
        }

        return 0;
    }
}
