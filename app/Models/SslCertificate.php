<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'domain',
        'san_domains',
        'issuer',
        'type',
        'certificate',
        'private_key',
        'ca_bundle',
        'cert_path',
        'key_path',
        'valid_from',
        'valid_to',
        'auto_renew',
        'force_https',
        'hsts_enabled',
        'status',
    ];

    protected $casts = [
        'san_domains' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'auto_renew' => 'boolean',
        'force_https' => 'boolean',
        'hsts_enabled' => 'boolean',
    ];

    protected $appends = ['days_remaining', 'is_expiring_soon'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->valid_to) {
            return 0;
        }

        return max(0, (int)now()->diffInDays($this->valid_to, false));
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->days_remaining <= 30 && $this->status === 'active';
    }
}
