<?php

namespace App\Jobs;

use App\Services\Billing\SuspensionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckOverdueInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SuspensionService $suspensionService): void
    {
        $results = $suspensionService->processSuspensions();
        Log::info("Checked overdue subscriptions. Applied Grace: {$results['grace_period_applied']}, Suspended: {$results['suspended']}.");
    }
}
