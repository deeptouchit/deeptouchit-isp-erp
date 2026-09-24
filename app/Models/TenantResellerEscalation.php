<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantResellerEscalation extends Model
{
    use HasFactory;

    protected $table = 'tenant_reseller_escalations';

    protected $guarded = ['id'];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller()
    {
        return $this->belongsTo(TenantReseller::class, 'reseller_id');
    }

    public function engineer()
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function getCategoryNameAttribute(): string
    {
        return match ($this->category) {
            'trunk_congestion' => 'CIR Trunk Congestion & Latency',
            'bgp_routing' => 'BGP Peering / BDIX Prefix Flap',
            'radius_sync' => 'FreeRADIUS AAA Disconnect / CoA',
            'vlan_allocation' => 'Sub-ISP VLAN & IP Pool Routing',
            'wholesale_billing' => 'Wholesale Bandwidth Billing Dispute',
            'olt_uplink_loss' => 'Distribution OLT 10G Uplink Loss',
            default => ucwords(str_replace('_', ' ', $this->category)),
        };
    }

    public function getImpactBadgeColorAttribute(): string
    {
        return match ($this->impact_level) {
            'critical_outage' => 'bg-rose-50 text-rose-700 border-rose-200',
            'high_degraded' => 'bg-amber-50 text-amber-800 border-amber-200',
            'medium_packet_loss' => 'bg-blue-50 text-blue-700 border-blue-200',
            'low_inquiry' => 'bg-slate-100 text-slate-700 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'bg-amber-50 text-amber-700 border-amber-200',
            'tier2_investigating' => 'bg-blue-50 text-blue-700 border-blue-200',
            'tier3_noc_escalated' => 'bg-purple-50 text-purple-700 border-purple-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
