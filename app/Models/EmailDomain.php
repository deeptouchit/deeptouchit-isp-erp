<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'domain',
        'status',
        'is_catchall_enabled',
        'catchall_destination',
        'dkim_status',
        'dkim_selector',
        'dkim_private_key',
        'dkim_public_key',
        'spf_record',
        'dmarc_record',
        'max_accounts',
        'max_quota_mb',
    ];

    protected $casts = [
        'is_catchall_enabled' => 'boolean',
        'max_accounts' => 'integer',
        'max_quota_mb' => 'integer',
    ];

    protected $hidden = [
        'dkim_private_key',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(EmailAccount::class);
    }
}
