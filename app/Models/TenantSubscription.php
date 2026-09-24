<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'datetime',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'next_billing_date' => 'date',
        'grace_period_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'suspended_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(SaasInvoice::class, 'tenant_subscription_id');
    }

    /**
     * Check if subscription is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']) && 
               ($this->current_period_end === null || $this->current_period_end->isFuture() || $this->current_period_end->isToday());
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        if ($this->status === 'grace_period') {
            return true;
        }

        return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if subscription is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Days remaining in current billing period.
     */
    public function daysRemaining(): int
    {
        if (!$this->current_period_end) {
            return 999;
        }
        return (int) ceil(now()->floatDiffInDays($this->current_period_end, false));
    }
}
