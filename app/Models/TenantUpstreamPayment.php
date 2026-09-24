<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUpstreamPayment extends Model
{
    use HasFactory;

    protected $table = 'tenant_upstream_payments';

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'invoice_id',
        'voucher_no',
        'amount',
        'payment_method',
        'bank_name',
        'cheque_no',
        'transaction_ref',
        'paid_at',
        'receipt_attachment',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUpstreamProvider::class, 'provider_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantUpstreamInvoice::class, 'invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMethodBadgeAttribute(): array
    {
        $methods = [
            'BANK_TRANSFER' => ['label' => 'Bank Transfer / BEFTN', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
            'CHEQUE' => ['label' => 'Bank Cheque', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'RTGS' => ['label' => 'RTGS Instant', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
            'CASH' => ['label' => 'Cash', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'OTHER' => ['label' => 'Other Method', 'class' => 'bg-slate-100 text-slate-700 border-slate-200'],
        ];

        return $methods[$this->payment_method] ?? ['label' => $this->payment_method, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
    }
}
