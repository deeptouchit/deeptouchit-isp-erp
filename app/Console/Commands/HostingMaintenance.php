<?php
namespace App\Console\Commands;

use App\Jobs\ScheduledServiceJob;
use Illuminate\Console\Command;

class HostingMaintenance extends Command
{
    protected $signature = 'hosting:maintenance {--renew-ssl : Renew SSL certificates}
        {--suspend-expired : Suspend expired accounts}
        {--generate-invoices : Generate invoices for upcoming billing}';
    
    protected $description = 'Perform hosting maintenance tasks';
    
    public function handle(): int
    {
        if ($this->option('renew-ssl')) {
            $this->info('Renewing SSL certificates...');
            ScheduledServiceJob::dispatch('renew-ssl');
            $this->info('SSL renewal job dispatched.');
        }
        
        if ($this->option('suspend-expired')) {
            $this->info('Suspending expired subscriptions...');
            ScheduledServiceJob::dispatch('suspend-expired');
            $this->info('Suspension job dispatched.');
        }
        
        if ($this->option('generate-invoices')) {
            $this->info('Generating invoices...');
            ScheduledServiceJob::dispatch('generate-invoices');
            $this->info('Invoice generation job dispatched.');
        }
        
        if (!$this->option('renew-ssl') && 
            !$this->option('suspend-expired') && 
            !$this->option('generate-invoices')) {
            $this->error('Please specify at least one task to perform.');
            $this->info('Example: php artisan hosting:maintenance --renew-ssl --suspend-expired');
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
