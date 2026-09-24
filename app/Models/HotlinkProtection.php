<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotlinkProtection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'domain',
        'is_enabled',
        'allowed_extensions',
        'allowed_referrers',
        'allow_direct_requests',
        'redirect_url',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allow_direct_requests' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
