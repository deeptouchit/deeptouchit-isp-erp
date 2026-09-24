<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantExpenseTransaction extends Model
{
    use HasFactory;

    protected $table = 'tenant_expense_transactions';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'voucher_no',
        'type',
        'title',
        'amount',
        'payment_method',
        'account_name',
        'payee_payer',
        'reference_no',
        'transaction_date',
        'status',
        'created_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected $appends = [
        'type_badge',
        'status_badge',
        'method_icon',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TenantExpenseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Visual Type Badge
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'INCOME' => [
                'label' => 'Income',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-arrow-down-left',
            ],
            default => [
                'label' => 'Expense',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-arrow-up-right',
            ],
        };
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
            'REJECTED' => [
                'label' => 'Rejected',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-xmark',
            ],
            default => [
                'label' => 'Pending Approval',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-clock',
            ],
        };
    }

    /**
     * Payment Method Icon
     */
    public function getMethodIconAttribute(): string
    {
        return match (strtoupper($this->payment_method ?? '')) {
            'BANK' => 'fas fa-building-columns text-indigo-500',
            'BKASH' => 'fas fa-mobile-screen text-pink-500',
            'NAGAD' => 'fas fa-wallet text-orange-500',
            'CHEQUE' => 'fas fa-money-check text-cyan-600',
            'PETTY_CASH' => 'fas fa-coins text-amber-500',
            default => 'fas fa-money-bill-1-wave text-emerald-600',
        };
    }

    /**
     * Generate Voucher Number (e.g. EXP-202609-001 / INC-202609-001)
     */
    public static function generateVoucherNo(int $tenantId, string $type = 'EXPENSE'): string
    {
        $prefix = ($type === 'INCOME' ? 'INC-' : 'EXP-') . date('Ym') . '-';
        $count = self::where('tenant_id', $tenantId)
            ->where('voucher_no', 'like', "{$prefix}%")
            ->count();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
