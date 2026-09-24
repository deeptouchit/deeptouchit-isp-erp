<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantBandwidthPlan extends Model
{
    use HasFactory;

    protected $table = 'tenant_bandwidth_plans';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'min_bandwidth_mbps',
        'max_bandwidth_mbps',
        'global_rate_per_mbps',
        'cdn_rate_per_mbps',
        'bdix_rate_per_mbps',
        'ggc_rate_per_mbps',
        'fna_rate_per_mbps',
        'others_rate_per_mbps',
        'flat_rate_per_mbps',
        'bundle_price',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'min_bandwidth_mbps' => 'decimal:2',
        'max_bandwidth_mbps' => 'decimal:2',
        'global_rate_per_mbps' => 'decimal:2',
        'cdn_rate_per_mbps' => 'decimal:2',
        'bdix_rate_per_mbps' => 'decimal:2',
        'ggc_rate_per_mbps' => 'decimal:2',
        'fna_rate_per_mbps' => 'decimal:2',
        'others_rate_per_mbps' => 'decimal:2',
        'flat_rate_per_mbps' => 'decimal:2',
        'bundle_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'formatted_slab_range',
        'status_badge',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TenantResellerBandwidth::class, 'bandwidth_plan_id');
    }

    /**
     * Get human-friendly slab range (e.g., 1 - 500 Mbps, 500 - 1000 Mbps / 1 Gbps, 1000+ Mbps)
     */
    public function getFormattedSlabRangeAttribute(): string
    {
        $min = (float) $this->min_bandwidth_mbps;
        $max = $this->max_bandwidth_mbps !== null ? (float) $this->max_bandwidth_mbps : null;

        $formatSpeed = function (float $mbps) {
            if ($mbps >= 1000) {
                $gbps = $mbps / 1000;
                return (floor($gbps) == $gbps ? (int) $gbps : round($gbps, 1)) . ' Gbps (' . (int) $mbps . 'M)';
            }
            return (int) $mbps . ' Mbps';
        };

        if ($min <= 0 && ($max === null || $max <= 0)) {
            return 'All Volumes (Universal)';
        }

        if ($max === null || $max <= 0) {
            return $formatSpeed($min) . ' +';
        }

        return $formatSpeed($min) . ' - ' . $formatSpeed($max);
    }

    /**
     * Calculate monthly bill across 6 traffic categories
     */
    public function calculateMonthlyBill(
        float $global = 0,
        float $cdn = 0,
        float $bdix = 0,
        float $ggc = 0,
        float $fna = 0,
        float $others = 0
    ): float {
        $cost = ($global * (float) $this->global_rate_per_mbps)
              + ($cdn * (float) $this->cdn_rate_per_mbps)
              + ($bdix * (float) $this->bdix_rate_per_mbps)
              + ($ggc * (float) $this->ggc_rate_per_mbps)
              + ($fna * (float) $this->fna_rate_per_mbps)
              + ($others * (float) $this->others_rate_per_mbps);

        return round($cost, 2);
    }

    public function getStatusBadgeAttribute(): array
    {
        return $this->is_active ? [
            'label' => 'Active',
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
        ] : [
            'label' => 'Inactive',
            'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            'dot' => 'bg-slate-400',
        ];
    }
}
