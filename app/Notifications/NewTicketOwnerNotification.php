<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketOwnerNotification extends Notification
{
    use Queueable;

    public $ticket;

    public function __construct(SupportTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable): array
    {
        // Database notification + optional Mail if SMTP configured
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'tenant_id' => $this->ticket->tenant_id,
            'tenant_name' => $this->ticket->tenant?->company_name ?? 'Tenant',
            'subject' => $this->ticket->subject,
            'department' => $this->ticket->department,
            'priority' => $this->ticket->priority,
            'type' => 'new_ticket',
            'message' => "New ticket {$this->ticket->ticket_number} created by {$this->ticket->tenant?->company_name}: '{$this->ticket->subject}'",
            'link' => route('owner.tickets.show', $this->ticket->id),
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->priority}] New Support Ticket: {$this->ticket->ticket_number}")
            ->line("Tenant **{$this->ticket->tenant?->company_name}** has created a new support ticket.")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Department:** {$this->ticket->department_name}")
            ->line("**Priority:** " . strtoupper($this->ticket->priority))
            ->action('View Ticket in Owner Panel', route('owner.tickets.show', $this->ticket->id))
            ->line('Please respond promptly to maintain high service levels.');
    }
}
