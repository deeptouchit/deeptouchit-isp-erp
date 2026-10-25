<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantOnu extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'onu_id' => 'integer',
        'running_state' => 'integer',
        'control_flag' => 'integer',
        'rx_power_dbm' => 'float',
        'tx_power_dbm' => 'float',
        'distance_m' => 'integer',
        'last_online_at' => 'datetime',
        'last_offline_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(TenantOlt::class, 'olt_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TenantCustomer::class, 'customer_id');
    }

    /**
     * Get display router MAC address (dedicated or fallback to auto_router_mac / linked customer)
     */
    public function getDisplayRouterMacAttribute(): ?string
    {
        if (!empty($this->router_mac)) {
            return $this->router_mac;
        }

        if (isset($this->attributes['auto_router_mac']) && !empty($this->attributes['auto_router_mac'])) {
            return $this->attributes['auto_router_mac'];
        }

        if ($this->relationLoaded('customer') && $this->customer) {
            return $this->customer->mac_address;
        }

        return null;
    }

    /**
     * Get Signal Quality label and color
     * 0 dBm to -20 dBm: Green
     * -21 dBm to -26 dBm: Yellow (Amber)
     * -27 dBm to -32 dBm: Red (Rose)
     */
    public function getSignalQualityAttribute(): array
    {
        $rx = $this->rx_power_dbm;
        if ($rx === null || $rx == 0) {
            return [
                'status' => 'unknown',
                'label' => 'N/A',
                'badge' => 'bg-slate-100 text-slate-600 border-slate-200',
                'color' => 'text-slate-500'
            ];
        }

        // 0 dBm to -20 dBm (Green)
        if ($rx >= -20.0) {
            return [
                'status' => 'good',
                'label' => 'Good',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'color' => 'text-emerald-600'
            ];
        }

        // -21 dBm to -26 dBm (Yellow / Amber)
        if ($rx >= -26.0 && $rx < -20.0) {
            return [
                'status' => 'warning',
                'label' => 'Warning',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'color' => 'text-amber-600'
            ];
        }

        // -27 dBm to -32 dBm and below (Red / Rose)
        return [
            'status' => 'critical',
            'label' => 'Critical',
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'color' => 'text-rose-600'
        ];
    }
}
