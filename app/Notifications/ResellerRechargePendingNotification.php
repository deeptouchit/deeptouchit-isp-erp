<?php

namespace App\Notifications;

use App\Models\TenantReseller;
use App\Models\TenantResellerRecharge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResellerRechargePendingNotification extends Notification
{
    use Queueable;

    public TenantResellerRecharge $recharge;
    public TenantReseller $reseller;
    public float $amount;
    public ?string $method;
    public ?string $trxId;

    public function __construct(
        TenantResellerRecharge $recharge,
        TenantReseller $reseller,
        float $amount,
        ?string $method = null,
        ?string $trxId = null
    ) {
        $this->recharge = $recharge;
        $this->reseller = $reseller;
        $this->amount = $amount;
        $this->method = $method;
        $this->trxId = $trxId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $resellerName = $this->reseller->name ?? 'Reseller';
        $methodLabel = $this->recharge->payment_method_badge['label'] ?? ($this->method ?? 'Cash/Bank');
        $trxInfo = $this->trxId ? " (TrxID: {$this->trxId})" : "";

        return [
            'recharge_id' => $this->recharge->id,
            'recharge_no' => $this->recharge->recharge_no,
            'reseller_id' => $this->reseller->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'trx_id' => $this->trxId,
            'type' => 'reseller_recharge_pending',
            'priority' => 'high',
            'title' => 'Reseller Wallet Recharge Request',
            'message' => "Reseller '{$resellerName}' submitted wallet recharge request #{$this->recharge->recharge_no} for ৳" . number_format($this->amount, 2) . " via {$methodLabel}{$trxInfo}. Approval required.",
            'sender_name' => $resellerName,
            'link' => route('tenant.resellers.recharge'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
