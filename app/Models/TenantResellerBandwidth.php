<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantResellerBandwidth extends Model
{
    use HasFactory;

    protected $table = 'tenant_reseller_bandwidth';

    protected $fillable = [
        'tenant_id',
        'reseller_id',
        'router_id',
        'bandwidth_plan_id',
        'interface_name',
        'vlan_id',
        'global_bandwidth_mbps',
        'bdix_bandwidth_mbps',
        'cdn_bandwidth_mbps',
        'ggc_bandwidth_mbps',
        'fna_bandwidth_mbps',
        'other_bandwidth_mbps',
        'total_bandwidth_mbps',
        'allocation_type',
        'rate_per_mbps',
        'global_rate_per_mbps',
        'bdix_rate_per_mbps',
        'cdn_rate_per_mbps',
        'ggc_rate_per_mbps',
        'fna_rate_per_mbps',
        'others_rate_per_mbps',
        'monthly_bill_amount',
        'mikrotik_queue_name',
        'current_usage_mbps',
        'peak_usage_mbps',
        'status',
        'notes',
        'last_sync_at',
    ];

    protected $casts = [
        'global_bandwidth_mbps' => 'decimal:2',
        'bdix_bandwidth_mbps' => 'decimal:2',
        'cdn_bandwidth_mbps' => 'decimal:2',
        'ggc_bandwidth_mbps' => 'decimal:2',
        'fna_bandwidth_mbps' => 'decimal:2',
        'other_bandwidth_mbps' => 'decimal:2',
        'total_bandwidth_mbps' => 'decimal:2',
        'rate_per_mbps' => 'decimal:2',
        'global_rate_per_mbps' => 'decimal:2',
        'bdix_rate_per_mbps' => 'decimal:2',
        'cdn_rate_per_mbps' => 'decimal:2',
        'ggc_rate_per_mbps' => 'decimal:2',
        'fna_rate_per_mbps' => 'decimal:2',
        'others_rate_per_mbps' => 'decimal:2',
        'monthly_bill_amount' => 'decimal:2',
        'current_usage_mbps' => 'decimal:2',
        'peak_usage_mbps' => 'decimal:2',
        'last_sync_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'allocation_type_badge',
        'utilization_percent',
        'formatted_total_bandwidth',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    public function bandwidthPlan(): BelongsTo
    {
        return $this->belongsTo(TenantBandwidthPlan::class, 'bandwidth_plan_id');
    }

    /**
     * Utilization Percentage (0-100)
     */
    public function getUtilizationPercentAttribute(): float
    {
        $total = (float) $this->total_bandwidth_mbps;
        if ($total <= 0) {
            return 0.0;
        }
        $usage = (float) $this->current_usage_mbps;
        return round(min(100.0, ($usage / $total) * 100), 1);
    }

    /**
     * Formatted Bandwidth (e.g., 500 Mbps or 1.50 Gbps)
     */
    public function getFormattedTotalBandwidthAttribute(): string
    {
        $mbps = (float) $this->total_bandwidth_mbps;
        if ($mbps >= 1000) {
            return round($mbps / 1000, 2) . ' Gbps';
        }
        return round($mbps, 0) . ' Mbps';
    }

    /**
     * Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'ACTIVE' => [
                'label' => 'Active Line',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
            ],
            'THROTTLED' => [
                'label' => 'Throttled (Capped)',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-gauge-high',
            ],
            'SUSPENDED' => [
                'label' => 'Suspended (Blocked)',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon' => 'fa-circle-pause',
            ],
            default => [
                'label' => $this->status,
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-circle',
            ],
        };
    }

    /**
     * Allocation Type Visual Badge
     */
    public function getAllocationTypeBadgeAttribute(): array
    {
        return match ($this->allocation_type) {
            'DEDICATED_CIR' => [
                'label' => 'Dedicated (CIR 1:1)',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-bolt',
            ],
            'BURSTABLE_MIR' => [
                'label' => 'Burstable (MIR)',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-water',
            ],
            'SHARED_POOL' => [
                'label' => 'Shared Pool',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-users-line',
            ],
            default => [
                'label' => str_replace('_', ' ', $this->allocation_type),
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-network-wired',
            ],
        };
    }
}
