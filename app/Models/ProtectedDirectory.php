<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtectedDirectory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'path',
        'realm',
        'domain',
        'is_active',
        'htpasswd_file_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ProtectedDirectoryUser::class);
    }
}
