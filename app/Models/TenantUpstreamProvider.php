<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantUpstreamProvider extends Model
{
    use HasFactory;

    protected $table = 'tenant_upstream_providers';

    protected $fillable = [
        'tenant_id',
        'name',
        'carrier_type',
        'contact_person',
        'phone',
        'email',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_branch',
        'routing_no',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(TenantUpstreamLink::class, 'provider_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TenantUpstreamInvoice::class, 'provider_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantUpstreamPayment::class, 'provider_id');
    }

    /**
     * Total purchased capacity across all active links in Mbps
     */
    public function getTotalCapacityAttribute(): float
    {
        return (float) $this->links()->where('status', 'ACTIVE')->sum('total_mbps');
    }

    /**
     * Total due balance
     */
    public function getTotalDueAttribute(): float
    {
        return (float) $this->invoices()->sum('due_amount');
    }

    /**
     * Carrier type badge styling
     */
    public function getTypeBadgeAttribute(): array
    {
        $types = [
            'IIG' => ['label' => 'IIG Carrier', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
            'ITC' => ['label' => 'ITC Link', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
            'NTTN' => ['label' => 'NTTN Transmission', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'BDIX' => ['label' => 'BDIX Peering', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'CACHE_PROVIDER' => ['label' => 'Cache / CDN', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
        ];

        return $types[$this->carrier_type] ?? ['label' => $this->carrier_type, 'class' => 'bg-slate-100 text-slate-700 border-slate-200'];
    }
}
