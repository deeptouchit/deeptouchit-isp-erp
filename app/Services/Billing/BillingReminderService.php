<?php

namespace App\Services\Billing;

use App\Models\SaasInvoice;
use App\Models\TenantSubscription;
use App\Models\Setting;
use App\Services\Sms\SmsTrackerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BillingReminderService
{
    protected SmsTrackerService $smsTracker;

    public function __construct(SmsTrackerService $smsTracker)
    {
        $this->smsTracker = $smsTracker;
    }

    /**
     * Send billing reminder notifications across timeline.
     */
    public function sendReminders(): array
    {
        $today = Carbon::today();
        $results = ['upcoming' => 0, 'due_today' => 0, 'overdue' => 0];

        // 1. Upcoming Invoices (Configurable Days Before Next Billing Date)
        $leadDays = (int)(Setting::get('invoice_generate_days_before') ?? 3);
        $upcomingTarget = $today->copy()->addDays($leadDays)->toDateString();
        $upcoming = TenantSubscription::with('tenant')->where('status', 'active')
            ->where('next_billing_date', $upcomingTarget)
            ->get();

        foreach ($upcoming as $sub) {
            $phone = $sub->tenant->phone ?? null;
            $msg = "Dear {$sub->tenant->name}, your SaaS subscription will renew on {$sub->next_billing_date}. Please ensure sufficient balance.";
            
            if ($phone) {
                $this->smsTracker->recordSms(
                    $sub->tenant_id,
                    $phone,
                    $msg,
                    'billing_reminder',
                    'Greenweb',
                    'delivered'
                );
            }

            Log::info("Sending upcoming renewal reminder to Tenant #{$sub->tenant_id} for date {$sub->next_billing_date}.");
            $results['upcoming']++;
        }

        // 2. Due Today Invoices
        $dueInvoices = SaasInvoice::with('tenant')->where('status', 'unpaid')
            ->where('due_date', $today->toDateString())
            ->get();

        foreach ($dueInvoices as $inv) {
            $phone = $inv->tenant->phone ?? null;
            $msg = "Urgent: Invoice #{$inv->invoice_no} for {$inv->tenant->name} of ৳" . number_format($inv->amount, 2) . " is due today. Please pay to avoid service disruption.";
            
            if ($phone) {
                $this->smsTracker->recordSms(
                    $inv->tenant_id,
                    $phone,
                    $msg,
                    'due_notice',
                    'Greenweb',
                    'delivered'
                );
            }

            Log::info("Sending payment due notification for Invoice #{$inv->invoice_no} to Tenant #{$inv->tenant_id}.");
            $results['due_today']++;
        }

        return $results;
    }
}
