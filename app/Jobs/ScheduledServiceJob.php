<?php
namespace App\Jobs;

use App\Models\Subscription;
use App\Services\NginxManager;
use App\Services\SSLManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduledServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected string $action;
    
    public function __construct(string $action)
    {
        $this->action = $action;
    }
    
    public function handle(SSLManager $sslManager, NginxManager $nginxManager): void
    {
        switch ($this->action) {
            case 'renew-ssl':
                $sslManager->renewSSL();
                break;
            case 'suspend-expired':
                $this->suspendExpiredSubscriptions($nginxManager);
                break;
            case 'generate-invoices':
                $this->generateNextBillingInvoices();
                break;
        }
    }
    
    private function suspendExpiredSubscriptions(NginxManager $nginxManager): void
    {
        $expired = Subscription::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();
        
        foreach ($expired as $subscription) {
            $subscription->update([
                'status' => 'suspended',
                'suspended_at' => now()
            ]);
            
            $nginxManager->suspendDomain($subscription->domain);
        }
    }
    
    private function generateNextBillingInvoices(): void
    {
        $dueForBilling = Subscription::where('status', 'active')
            ->whereDate('next_billing_date', '<=', now()->addDays(7))
            ->get();
        
        foreach ($dueForBilling as $subscription) {
            // Generate invoice logic
        }
    }
}
