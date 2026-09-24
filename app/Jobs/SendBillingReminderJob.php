<?php

namespace App\Jobs;

use App\Services\Billing\BillingReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBillingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BillingReminderService $reminderService): void
    {
        $results = $reminderService->sendReminders();
        Log::info("Dispatched billing reminders. Upcoming: {$results['upcoming']}, Due: {$results['due_today']}.");
    }
}
