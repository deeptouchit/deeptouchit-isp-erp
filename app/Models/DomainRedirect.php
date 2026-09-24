<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'website_id',
        'source_domain',
        'source_path',
        'target_url',
        'redirect_code',
        'www_redirect_type',
        'match_wildcard',
        'status',
    ];

    protected $casts = [
        'redirect_code' => 'integer',
        'match_wildcard' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
