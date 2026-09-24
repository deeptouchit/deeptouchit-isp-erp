<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaasInvoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function subscription()
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function items()
    {
        return $this->hasMany(SubscriptionInvoiceItem::class, 'saas_invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'saas_invoice_id');
    }

    /**
     * Calculate live due amount formula:
     * due_amount = total_amount - paid_amount - credit_amount
     */
    public function getCalculatedDueAttribute(): float
    {
        $due = (float)$this->amount - (float)$this->paid_amount - (float)$this->credit_amount;
        return max(0, $due);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }
}
