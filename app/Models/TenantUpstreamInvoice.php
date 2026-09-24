<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantUpstreamInvoice extends Model
{
    use HasFactory;

    protected $table = 'tenant_upstream_invoices';

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'link_id',
        'invoice_no',
        'billing_month',
        'bandwidth_cost',
        'transmission_cost',
        'vat_tax',
        'other_charges',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'due_date',
        'item_breakdown',
        'notes',
    ];

    protected $casts = [
        'billing_month' => 'date',
        'due_date' => 'date',
        'bandwidth_cost' => 'decimal:2',
        'transmission_cost' => 'decimal:2',
        'vat_tax' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'item_breakdown' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUpstreamProvider::class, 'provider_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(TenantUpstreamLink::class, 'link_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantUpstreamPayment::class, 'invoice_id');
    }

    public function getStatusBadgeAttribute(): array
    {
        $statusMap = [
            'PAID' => ['label' => 'Paid', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'PARTIAL' => ['label' => 'Partial', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'UNPAID' => ['label' => 'Unpaid', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
        ];

        return $statusMap[$this->payment_status] ?? ['label' => $this->payment_status, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
    }
}
