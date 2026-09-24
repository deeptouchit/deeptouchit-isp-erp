<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketActivityNotification extends Notification
{
    use Queueable;

    public SupportTicket $ticket;
    public string $title;
    public string $message;
    public string $type;
    public ?string $senderName;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        SupportTicket $ticket,
        string $title,
        string $message,
        string $type = 'ticket_activity',
        ?string $senderName = null
    ) {
        $this->ticket = $ticket;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->senderName = $senderName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $isReseller = method_exists($notifiable, 'isResellerUser') ? $notifiable->isResellerUser() : false;
        
        $link = $isReseller
            ? route('reseller.tickets.show', $this->ticket->id)
            : route('tenant.tickets.show', $this->ticket->id);

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'type' => $this->type,
            'priority' => $this->ticket->priority ?? 'medium',
            'title' => $this->title,
            'message' => $this->message,
            'sender_name' => $this->senderName,
            'link' => $link,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
