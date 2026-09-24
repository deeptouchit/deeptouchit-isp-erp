<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantResellerRecharge extends Model
{
    use HasFactory;

    protected $table = 'tenant_reseller_recharges';

    protected $fillable = [
        'tenant_id',
        'reseller_id',
        'recharge_no',
        'amount',
        'bonus_amount',
        'total_credited',
        'payment_method',
        'gateway_trx_id',
        'bank_name',
        'bank_branch',
        'bank_account_no',
        'deposit_date',
        'slip_path',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_credited' => 'decimal:2',
        'deposit_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'payment_method_badge',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Visual Status Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'APPROVED' => [
                'label' => 'Approved',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
            ],
            'PENDING' => [
                'label' => 'Pending Approval',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
            ],
            'REJECTED' => [
                'label' => 'Rejected',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => $this->status,
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-circle-question',
            ],
        };
    }

    /**
     * Payment Method Badge
     */
    public function getPaymentMethodBadgeAttribute(): array
    {
        return match ($this->payment_method) {
            'BKASH', 'BKASH_MERCHANT' => [
                'label' => 'bKash Merchant',
                'class' => 'bg-pink-50 text-pink-700 border-pink-200',
                'icon' => 'fa-mobile-screen',
            ],
            'BANGLA_QR' => [
                'label' => 'Bangla QR',
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
                'icon' => 'fa-qrcode',
            ],
            'NAGAD' => [
                'label' => 'Nagad',
                'class' => 'bg-orange-50 text-orange-700 border-orange-200',
                'icon' => 'fa-mobile-screen',
            ],
            'ROCKET' => [
                'label' => 'Rocket',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-mobile-screen',
            ],
            'BANK_TRANSFER', 'BANK_GATEWAY' => [
                'label' => 'Bank Gateway',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-building-columns',
            ],
            'CASH' => [
                'label' => 'Cash',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-money-bill-wave',
            ],
            'CARD', 'ONLINE_GATEWAY' => [
                'label' => 'Online PGW',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-credit-card',
            ],
            default => [
                'label' => str_replace('_', ' ', $this->payment_method),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-receipt',
            ],
        };
    }

    /**
     * Generate unique recharge tracking number (e.g. RCH-20260908-0001)
     */
    public static function generateRechargeNo(int $tenantId): string
    {
        $prefix = 'RCH-' . date('Ymd') . '-';
        $count = self::where('tenant_id', $tenantId)
            ->where('recharge_no', 'like', "{$prefix}%")
            ->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
