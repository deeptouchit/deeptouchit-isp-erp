<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DnsZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'domain',
        'primary_ns',
        'secondary_ns',
        'admin_email',
        'serial',
        'refresh',
        'retry',
        'expire',
        'ttl',
        'status',
        'dnssec_enabled',
        'zone_file_path',
    ];

    protected $casts = [
        'refresh' => 'integer',
        'retry' => 'integer',
        'expire' => 'integer',
        'ttl' => 'integer',
        'dnssec_enabled' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }
}
