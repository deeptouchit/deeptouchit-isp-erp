<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantResellerWalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'tenant_reseller_wallet_transactions';

    protected $fillable = [
        'tenant_id',
        'reseller_id',
        'trx_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'payment_method',
        'reference_no',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    protected $appends = [
        'type_badge',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Type visual badge
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'CREDIT' => [
                'label' => 'Credit (+Topup)',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-arrow-down-left',
            ],
            default => [
                'label' => 'Debit (-Deduction)',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-arrow-up-right',
            ],
        };
    }

    /**
     * Generate unique transaction ID (e.g. WTX-20260908-001)
     */
    public static function generateTrxId(int $tenantId): string
    {
        $prefix = 'WTX-' . date('Ymd') . '-';
        $count = self::where('tenant_id', $tenantId)
            ->where('trx_id', 'like', "{$prefix}%")
            ->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
