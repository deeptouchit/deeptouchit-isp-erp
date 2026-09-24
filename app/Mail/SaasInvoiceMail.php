<?php

namespace App\Mail;

use App\Models\SaasInvoice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaasInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public SaasInvoice $invoice,
        public string $billingEmail,
        public array $companySettings = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = $this->companySettings['app_name'] ?? Setting::get('app_name', 'SomitySoft');
        $isPaid = $this->invoice->status === 'paid';
        $statusLabel = $isPaid ? 'Payment Receipt' : 'Invoice Statement';

        return new Envelope(
            from: new Address($this->billingEmail, $appName . ' Billing'),
            replyTo: [
                new Address($this->billingEmail, $appName . ' Billing Department'),
            ],
            subject: "[{$statusLabel} #{$this->invoice->invoice_no}] {$appName} Subscription Billing",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.saas-invoice',
            with: [
                'invoice' => $this->invoice,
                'tenant' => $this->invoice->tenant,
                'billingEmail' => $this->billingEmail,
                'settings' => $this->companySettings,
            ],
        );
    }
}
