<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplyNotification extends Notification
{
    use Queueable;

    public $ticket;
    public $messageObj;
    public $recipientType; // 'owner' or 'tenant'

    public function __construct(SupportTicket $ticket, TicketMessage $messageObj, string $recipientType = 'owner')
    {
        $this->ticket = $ticket;
        $this->messageObj = $messageObj;
        $this->recipientType = $recipientType;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $senderName = $this->recipientType === 'owner' 
            ? ($this->ticket->tenant?->company_name ?? 'Tenant')
            : 'SomitySoft SaaS Support';

        $link = $this->recipientType === 'owner'
            ? route('owner.tickets.show', $this->ticket->id)
            : route('tenant.tickets.show', $this->ticket->id);

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'type' => 'ticket_reply',
            'sender_type' => $this->messageObj->sender_type,
            'message' => "New reply on ticket {$this->ticket->ticket_number} from {$senderName}",
            'snippet' => \Illuminate\Support\Str::limit($this->messageObj->message, 80),
            'link' => $link,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
