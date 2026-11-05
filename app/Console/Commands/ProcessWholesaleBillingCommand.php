<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Billing\WholesaleBillingAutomationService;
use Illuminate\Console\Command;

class ProcessWholesaleBillingCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:process-wholesale 
                            {--month= : Target billing month in Y-m format (defaults to current month)}
                            {--tenant= : Specific Tenant ID to process}
                            {--no-wallet-deduct : Disable auto deduction from reseller wallet}';

    /**
     * The console command description.
     */
    protected $description = 'Automate monthly wholesale bandwidth trunk & SaaS panel billing for Sub-ISP resellers';

    /**
     * Execute the console command.
     */
    public function handle(WholesaleBillingAutomationService $service): int
    {
        $month = $this->option('month') ?: now()->format('Y-m');
        $autoDeduct = !$this->option('no-wallet-deduct');
        $tenantId = $this->option('tenant');

        $this->info("=================================================");
        $this->info("   WHOLESALE RESELLER MONTHLY BILLING ENGINE     ");
        $this->info("   Target Month: {$month} | Auto-Deduct: " . ($autoDeduct ? 'ENABLED' : 'DISABLED'));
        $this->info("=================================================");

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Tenant ID #{$tenantId} not found.");
                return Command::FAILURE;
            }

            $this->comment("Processing single tenant: [{$tenant->id}] {$tenant->name}...");
            $result = $service->generateMonthlyBillsForTenant($tenant, $month, $autoDeduct);

            if ($result['success']) {
                $this->info($result['message']);
            } else {
                $this->warn($result['message']);
            }

            return Command::SUCCESS;
        }

        // Process all tenants
        $this->comment("Processing all active tenants across the platform...");
        $result = $service->generateMonthlyBillsForAllTenants($month, $autoDeduct);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Target Billing Month', $result['month']],
                ['Tenants Evaluated', $result['tenants_count']],
                ['Invoices Generated', $result['total_generated']],
                ['Already Billed / Skipped', $result['total_skipped']],
                ['Total Amount Invoiced', '৳ ' . number_format($result['total_billed'], 2)],
                ['Auto-Settled from Wallet', '৳ ' . number_format($result['total_paid'], 2)],
            ]
        );

        $this->info(" Wholesale Billing Engine completed successfully.");

        return Command::SUCCESS;
    }
}

