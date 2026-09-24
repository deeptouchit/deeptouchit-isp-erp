<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SystemHeartbeatCommand extends Command
{
    protected $signature = 'system:heartbeat';
    protected $description = 'Update cron scheduler heartbeat timestamp for health monitoring';

    public function handle(): int
    {
        Setting::set('cron_last_heartbeat_at', now()->toDateTimeString());
        $this->info('Cron heartbeat updated at ' . now()->toDateTimeString());

        return Command::SUCCESS;
    }
}
