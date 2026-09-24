<?php

namespace App\Console\Commands;

use App\Services\Billing\AutomationEngineService;
use Illuminate\Console\Command;

class ProcessBillingEngineCommand extends Command
{
    protected $signature = 'billing:process-subscriptions {--triggered-by=cron}';
    protected $description = 'Process master SaaS billing engine (invoices, wallet auto-renewals, grace periods, suspensions, reminders)';

    public function handle(AutomationEngineService $engine): int
    {
        $this->info('Starting SaaS Subscription Billing Engine...');

        $triggeredBy = $this->option('triggered-by') ?: 'cron';
        $result = $engine->runFullEngine($triggeredBy);

        $this->info($result['summary']);
        $this->info("Completed in {$result['duration_ms']}ms with status: {$result['status']}");

        return Command::SUCCESS;
    }
}
