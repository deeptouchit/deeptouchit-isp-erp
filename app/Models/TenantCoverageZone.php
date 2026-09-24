<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantCoverageZone extends Model
{
    use HasFactory;

    protected $table = 'tenant_coverage_zones';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tenant Relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Associated Reseller / Sub-ISP Partner (Nullable for ISP Direct)
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    /**
     * Assigned Field Technician / Staff
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Subscribers attached to this Coverage Zone
     */
    public function customers(): HasMany
    {
        return $this->hasMany(TenantCustomer::class, 'zone_id');
    }

    /**
     * Helper to get total subscribers count (via zone_id or matching name)
     */
    public function getSubscribersCountAttribute(): int
    {
        return TenantCustomer::where('tenant_id', $this->tenant_id)
            ->where(function($q) {
                $q->where('zone_id', $this->id)
                  ->orWhere('zone', $this->name);
            })
            ->count();
    }

    /**
     * Helper to get total active subscribers count
     */
    public function getActiveSubscribersCountAttribute(): int
    {
        return TenantCustomer::where('tenant_id', $this->tenant_id)
            ->where('status', 'active')
            ->where(function($q) {
                $q->where('zone_id', $this->id)
                  ->orWhere('zone', $this->name);
            })
            ->count();
    }

    /**
     * Helper to get total monthly revenue from this zone
     */
    public function getTotalMrrAttribute(): float
    {
        return (float) TenantCustomer::where('tenant_id', $this->tenant_id)
            ->where(function($q) {
                $q->where('zone_id', $this->id)
                  ->orWhere('zone', $this->name);
            })
            ->sum('monthly_bill');
    }
}
