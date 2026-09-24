<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRoute extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'distance' => 'integer',
        'scope' => 'integer',
        'target_scope' => 'integer',
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

    /**
     * Get Route type visual badge styling
     */
    public function getTypeBadgeAttribute(): array
    {
        return match (strtolower($this->type)) {
            'bgp' => [
                'label' => 'BGP Dynamic',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'ospf' => [
                'label' => 'OSPF Internal',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'connected' => [
                'label' => 'Connected (DAC)',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            ],
            'blackhole' => [
                'label' => 'Blackhole (Null0)',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
            ],
            default => [
                'label' => 'Static Gateway',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
        };
    }

    /**
     * Get Route status visual badge
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
