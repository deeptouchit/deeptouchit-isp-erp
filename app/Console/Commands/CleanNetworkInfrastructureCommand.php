<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanNetworkInfrastructureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:clean {--force : Force truncate without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all Network & Infrastructure tables and reset auto-increment index to 1';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to completely clean and reset all Network & Infrastructure tables? This action cannot be undone!')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->warn('Starting Network & Infrastructure clean-up...');

        // 1. Temporarily disable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Unlink customer foreign keys so customers remain intact
        if (Schema::hasTable('tenant_customers')) {
            DB::table('tenant_customers')->update([
                'package_id' => null,
                'router_id' => null,
                'olt_id' => null,
            ]);
            $this->info('Unlinked router_id, olt_id, package_id on tenant_customers.');
        }

        // 3. Network & Infrastructure tables list
        $tables = [
            // ONU / ONT
            'tenant_onus',
            // OLT Devices & Licenses
            'tenant_olts',
            // MikroTik Fleet
            'tenant_routers',
            // NAS & RADIUS
            'tenant_nas',
            'nas',
            'radcheck',
            'radreply',
            'radgroupcheck',
            'radgroupreply',
            'radusergroup',
            'radacct',
            'radpostauth',
            // Internet Packages
            'tenant_internet_packages',
            // IP Pools & IP Blocks
            'tenant_ip_pools',
            // VLANs
            'tenant_vlans',
            // Routing & BGP
            'tenant_routes',
            // Audit & Automation Logs related to Network
            'network_audit_logs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info("✓ Truncated [{$table}] and reset auto-increment index to 1.");
            } else {
                $this->line("- Table [{$table}] does not exist, skipped.");
            }
        }

        // 4. Re-enable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('🎉 All Network & Infrastructure modules successfully cleaned and reset to index 1.');

        return 0;
    }
}
