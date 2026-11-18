<?php

namespace App\Notifications;

use App\Models\TenantReseller;
use App\Models\TenantResellerRecharge;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResellerRechargeStatusNotification extends Notification
{
    use Queueable;

    public TenantResellerRecharge $recharge;
    public TenantReseller $reseller;
    public string $status;
    public ?string $reason;

    public function __construct(
        TenantResellerRecharge $recharge,
        TenantReseller $reseller,
        string $status,
        ?string $reason = null
    ) {
        $this->recharge = $recharge;
        $this->reseller = $reseller;
        $this->status = $status;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $isApproved = $this->status === 'APPROVED';
        $amount = (float) ($this->recharge->total_credited > 0 ? $this->recharge->total_credited : $this->recharge->amount);
        $tenantId = $this->reseller->tenant_id ?? $notifiable->tenant_id ?? null;
        $currencySymbol = Setting::get('currency_symbol', '৳', $tenantId);

        $title = $isApproved
            ? __('Wallet Recharge Approved')
            : __('Wallet Recharge Rejected');

        $message = $isApproved
            ? __('Your wallet recharge request #:number for :currency has been approved! Balance has been credited.', [
                'number' => $this->recharge->recharge_no,
                'currency' => $currencySymbol . ' ' . number_format($amount, 2),
            ])
            : __('Your wallet recharge request #:number for :currency was rejected.:reason', [
                'number' => $this->recharge->recharge_no,
                'currency' => $currencySymbol . ' ' . number_format((float) $this->recharge->amount, 2),
                'reason' => $this->reason ? ' ' . __('Reason') . ': ' . $this->reason : '',
            ]);

        return [
            'recharge_id' => $this->recharge->id,
            'recharge_no' => $this->recharge->recharge_no,
            'reseller_id' => $this->reseller->id,
            'amount' => $amount,
            'status' => $this->status,
            'type' => 'reseller_recharge_status',
            'priority' => $isApproved ? 'normal' : 'high',
            'title' => $title,
            'message' => $message,
            'sender_name' => 'ISP Administration',
            'link' => route('reseller.recharge.index'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
