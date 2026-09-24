<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryIndexing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'domain',
        'path',
        'is_root',
        'indexing_type',
    ];

    protected $casts = [
        'is_root' => 'boolean',
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
