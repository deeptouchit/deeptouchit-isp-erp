<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantResellerInvoice extends Model
{
    use HasFactory;

    protected $table = 'tenant_reseller_invoices';

    protected $fillable = [
        'tenant_id',
        'reseller_id',
        'invoice_no',
        'billing_month',
        'type',
        'subtotal',
        'amount',
        'discount',
        'vat_tax',
        'paid_amount',
        'due_amount',
        'payment_status',
        'payment_method',
        'paid_at',
        'period_start',
        'period_end',
        'due_date',
        'notes',
        'item_details',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat_tax' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'billing_month' => 'date',
        'paid_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'item_details' => 'array',
    ];

    protected $appends = [
        'status_badge',
        'type_label',
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
     * Payment Status Badge Styling
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->payment_status) {
            'PAID' => [
                'label' => 'Paid',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-check-circle',
            ],
            'PARTIAL' => [
                'label' => 'Partial',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-circle-half-stroke',
            ],
            'CANCELLED' => [
                'label' => 'Cancelled',
                'class' => 'bg-slate-50 text-slate-600 border-slate-200',
                'icon' => 'fa-ban',
            ],
            default => [
                'label' => 'Unpaid / Due',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-clock',
            ],
        };
    }

    /**
     * Invoice Type Human Label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'BANDWIDTH_WHOLESALE' => 'Bandwidth Wholesale',
            'MANUAL_CHARGE' => 'Manual / Extra Charge',
            default => 'Software / Panel Subscription',
        };
    }

    /**
     * Auto generate Invoice Number (e.g. RINV-202609-001)
     */
    public static function generateInvoiceNo(int $tenantId): string
    {
        $prefix = 'RINV-' . date('Ym') . '-';
        $count = self::where('tenant_id', $tenantId)
            ->where('invoice_no', 'like', "{$prefix}%")
            ->count();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
