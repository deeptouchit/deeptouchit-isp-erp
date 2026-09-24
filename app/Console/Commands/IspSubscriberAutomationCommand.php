<?php

namespace App\Console\Commands;

use App\Services\Billing\IspCustomerAutomationService;
use Illuminate\Console\Command;

class IspSubscriberAutomationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:process-subscribers 
                            {--mode=auto : Running mode: auto (dynamic from UI settings), midnight, reminder, autocut, billing, or all}
                            {--tenant= : Optional specific Tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Master ISP Subscriber Automation Engine (Driven dynamically by admin/settings/automation rules)';

    /**
     * Execute the console command.
     */
    public function handle(IspCustomerAutomationService $service): int
    {
        $mode = $this->option('mode') ?: 'auto';
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        $this->info("🚀 Starting ISP Subscriber Automation Engine (Mode: [{$mode}])...");

        if ($mode === 'auto') {
            $results = $service->runDynamicSchedule($tenantId);
            if (!empty($results)) {
                foreach ($results as $res) {
                    $this->info("✅ " . ($res['summary'] ?? 'Task finished.'));
                }
            } else {
                $this->line("ℹ️ Dynamic Scheduler checked UI settings: No tasks scheduled for current minute.");
            }
        } elseif ($mode === 'midnight' || $mode === 'all') {
            $this->line("⏳ Running Midnight Batch (Invoice Generation & Auto-Cut Expiry Enforcement)...");
            $result = $service->runMidnightBatch($tenantId);
            $this->info("✅ " . $result['summary']);
        } elseif ($mode === 'reminder') {
            $this->line("⏳ Running Daytime Reminders Batch (SMS Dispatcher)...");
            $result = $service->runDaytimeReminders($tenantId);
            $this->info("✅ " . $result['summary']);
        }

        $this->info("🎉 ISP Subscriber Automation finished successfully.");

        return Command::SUCCESS;
    }
}
