<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected string $reason;
    
    public function __construct(string $reason = 'Payment overdue')
    {
        $this->reason = $reason;
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        $name = $notifiable->first_name ? ($notifiable->first_name . ' ' . $notifiable->last_name) : ($notifiable->username ?? 'Customer');
        return (new MailMessage)
            ->subject('Account Suspension Notice - ' . config('app.name'))
            ->greeting('Hello ' . $name)
            ->line('Your hosting account has been suspended.')
            ->line('Reason: ' . $this->reason)
            ->action('Contact Support', url('/tickets'))
            ->line('To reactivate your account, please make a payment or resolve any overdue invoices.');
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Account Suspended',
            'body' => 'Your account has been suspended due to ' . $this->reason,
            'action_url' => '/billing/invoices',
            'created_at' => now(),
        ];
    }
}
