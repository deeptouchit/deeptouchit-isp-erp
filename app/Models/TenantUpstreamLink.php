<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUpstreamLink extends Model
{
    use HasFactory;

    protected $table = 'tenant_upstream_links';

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'router_id',
        'link_name',
        'interface_port',
        'circuit_id',
        'global_mbps',
        'bdix_mbps',
        'cdn_mbps',
        'ggc_mbps',
        'fna_mbps',
        'other_mbps',
        'total_mbps',
        'global_rate',
        'bdix_rate',
        'cdn_rate',
        'ggc_rate',
        'fna_rate',
        'other_rate',
        'monthly_transmission_cost',
        'est_monthly_bill',
        'status',
    ];

    protected $casts = [
        'global_mbps' => 'decimal:2',
        'bdix_mbps' => 'decimal:2',
        'cdn_mbps' => 'decimal:2',
        'ggc_mbps' => 'decimal:2',
        'fna_mbps' => 'decimal:2',
        'other_mbps' => 'decimal:2',
        'total_mbps' => 'decimal:2',
        'global_rate' => 'decimal:2',
        'bdix_rate' => 'decimal:2',
        'cdn_rate' => 'decimal:2',
        'ggc_rate' => 'decimal:2',
        'fna_rate' => 'decimal:2',
        'other_rate' => 'decimal:2',
        'monthly_transmission_cost' => 'decimal:2',
        'est_monthly_bill' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(TenantUpstreamProvider::class, 'provider_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    /**
     * Recalculate total bandwidth and estimated monthly bill
     */
    public function recalculateTotals(): void
    {
        $this->total_mbps = (float) $this->global_mbps
                          + (float) $this->bdix_mbps
                          + (float) $this->cdn_mbps
                          + (float) $this->ggc_mbps
                          + (float) $this->fna_mbps
                          + (float) $this->other_mbps;

        $bandwidthCost = ((float) $this->global_mbps * (float) $this->global_rate)
                       + ((float) $this->bdix_mbps * (float) $this->bdix_rate)
                       + ((float) $this->cdn_mbps * (float) $this->cdn_rate)
                       + ((float) $this->ggc_mbps * (float) $this->ggc_rate)
                       + ((float) $this->fna_mbps * (float) $this->fna_rate)
                       + ((float) $this->other_mbps * (float) $this->other_rate);

        $this->est_monthly_bill = round($bandwidthCost + (float) $this->monthly_transmission_cost, 2);
    }
}
