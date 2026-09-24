<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantVlan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'vlan_id' => 'integer',
        'mtu' => 'integer',
        'dhcp_enabled' => 'boolean',
        'is_sync_mikrotik' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(TenantOlt::class, 'olt_id');
    }

    /**
     * Get VLAN type visual badge styling
     */
    public function getTypeBadgeAttribute(): array
    {
        return match (strtolower($this->type)) {
            'management' => [
                'label' => 'Management (OLT/SW)',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'corporate' => [
                'label' => 'Corporate Leased Line',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'cgnat' => [
                'label' => 'CGNAT Trunk',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            ],
            'voice' => [
                'label' => 'VoIP Voice',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            'tr069' => [
                'label' => 'TR-069 ACS',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            default => [
                'label' => 'Service (PPPoE / Client)',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
        };
    }

    /**
     * Get VLAN status visual badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'disabled' => [
                'label' => 'Disabled',
                'class' => 'bg-slate-100 text-slate-600 border-slate-200',
                'dot' => 'bg-slate-400',
            ],
            default => [
                'label' => 'Active',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
            ],
        };
    }
}
