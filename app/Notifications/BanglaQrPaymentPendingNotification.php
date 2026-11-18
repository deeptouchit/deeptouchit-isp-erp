<?php

namespace App\Notifications;

use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BanglaQrPaymentPendingNotification extends Notification
{
    use Queueable;

    public TenantCustomerPayment $payment;
    public TenantCustomer $customer;
    public ?TenantReseller $reseller;
    public float $amount;
    public ?string $trxId;

    public function __construct(
        TenantCustomerPayment $payment,
        TenantCustomer $customer,
        ?TenantReseller $reseller,
        float $amount,
        ?string $trxId = null
    ) {
        $this->payment = $payment;
        $this->customer = $customer;
        $this->reseller = $reseller;
        $this->amount = $amount;
        $this->trxId = $trxId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $isReseller = method_exists($notifiable, 'isResellerUser') ? $notifiable->isResellerUser() : false;
        $resellerName = $this->reseller?->name ?? 'Partner';
        $tenantId = $this->customer->tenant_id ?? $notifiable->tenant_id ?? null;
        $currencySymbol = Setting::get('currency_symbol', '৳', $tenantId);

        $link = $isReseller
            ? route('reseller.customers.show', $this->customer->id)
            : route('tenant.customers.show', $this->customer->id);

        return [
            'payment_id' => $this->payment->id,
            'customer_id' => $this->customer->id,
            'invoice_no' => $this->payment->invoice_no,
            'amount' => $this->amount,
            'trx_id' => $this->trxId,
            'type' => 'bangla_qr_pending',
            'priority' => 'high',
            'title' => __('Bangla QR Payment Pending Approval'),
            'message' => __('Bangla QR payment of :currency submitted for customer :customer (:username) by :reseller. TrxID: :trx. Line activation is pending approval.', [
                'currency' => $currencySymbol . ' ' . number_format($this->amount, 2),
                'customer' => $this->customer->name,
                'username' => $this->customer->username,
                'reseller' => $resellerName,
                'trx' => $this->trxId ?: __('N/A'),
            ]),
            'sender_name' => $resellerName,
            'link' => $link,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
