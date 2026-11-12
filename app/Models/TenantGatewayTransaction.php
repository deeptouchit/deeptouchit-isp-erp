<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantGatewayTransaction extends Model
{
    use HasFactory;

    protected $table = 'tenant_gateway_transactions';

    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'gateway_trx_id',
        'gateway',
        'purpose',
        'customer_id',
        'reseller_id',
        'user_id',
        'reference_id',
        'amount',
        'fee_amount',
        'net_amount',
        'currency',
        'payer_account',
        'payer_name',
        'status',
        'status_message',
        'ip_address',
        'gateway_payload',
        'initiated_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_payload' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'gateway_badge',
        'purpose_label',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Visual Status Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'SUCCESS' => [
                'label' => __('Success'),
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check-circle',
            ],
            'PENDING' => [
                'label' => __('Pending / Processing'),
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
            ],
            'FAILED' => [
                'label' => __('Failed'),
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            'REFUNDED' => [
                'label' => __('Refunded'),
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-rotate-left',
            ],
            default => [
                'label' => __('Cancelled'),
                'class' => 'bg-slate-50 text-slate-600 border-slate-200',
                'icon' => 'fa-ban',
            ],
        };
    }

    /**
     * Visual Gateway Badge
     */
    public function getGatewayBadgeAttribute(): array
    {
        $gw = strtolower($this->gateway ?? '');
        return match ($gw) {
            'bkash' => [
                'label' => __('bKash PGW'),
                'class' => 'bg-pink-50 text-pink-700 border-pink-200',
                'icon' => 'fa-mobile-screen-button',
            ],
            'nagad' => [
                'label' => __('Nagad Direct'),
                'class' => 'bg-orange-50 text-orange-700 border-orange-200',
                'icon' => 'fa-wallet',
            ],
            'rocket' => [
                'label' => __('Rocket (DBBL)'),
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-building-columns',
            ],
            'sslcommerz' => [
                'label' => __('SSLCommerz (Cards/NetBanking)'),
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-credit-card',
            ],
            'shurjopay' => [
                'label' => __('ShurjoPay'),
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
                'icon' => 'fa-money-bill-transfer',
            ],
            'stripe' => [
                'label' => __('Stripe International'),
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-globe',
            ],
            default => [
                'label' => $this->gateway ? strtoupper($this->gateway) : __('Online'),
                'class' => 'bg-slate-50 text-slate-700 border-slate-200',
                'icon' => 'fa-globe',
            ],
        };
    }

    /**
     * Purpose Label
     */
    public function getPurposeLabelAttribute(): string
    {
        return match ($this->purpose) {
            'CUSTOMER_BILL' => __('Subscriber Monthly Bill'),
            'CUSTOMER_RECHARGE' => __('Prepaid Customer Recharge'),
            'RESELLER_TOPUP' => __('Sub-ISP Wallet Recharge'),
            'RESELLER_INVOICE' => __('Wholesale Bandwidth Invoice'),
            'MANUAL' => __('Manual Web Payment'),
            default => __('Online Transaction'),
        };
    }

    /**
     * Generate Unique Transaction Identifier (e.g. PGW-20260914-0001)
     */
    public static function generateTrxId(int $tenantId): string
    {
        $prefix = 'PGW-' . date('Ymd') . '-';
        $count = self::where('tenant_id', $tenantId)
            ->where('transaction_id', 'like', "{$prefix}%")
            ->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
