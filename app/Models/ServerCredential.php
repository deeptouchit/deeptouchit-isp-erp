<?php

namespace App\Models;

use App\Enums\Infrastructure\ServerCredentialStatus;
use App\Enums\Infrastructure\ServerCredentialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'credential_type',
        'name',
        'username',
        'encrypted_secret',
        'fingerprint',
        'expires_at',
        'last_rotated_at',
        'status',
    ];

    /**
     * Strictly hide the encrypted_secret from array/JSON serialization,
     * API resources, and Inertia props.
     */
    protected $hidden = [
        'encrypted_secret',
    ];

    protected $casts = [
        'credential_type' => ServerCredentialType::class,
        'status' => ServerCredentialStatus::class,
        'encrypted_secret' => 'encrypted',
        'expires_at' => 'datetime',
        'last_rotated_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ServerCredentialStatus::ACTIVE);
    }
}
