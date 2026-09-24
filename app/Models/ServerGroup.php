<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServerGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'location',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($group) {
            if (empty($group->uuid)) {
                $group->uuid = (string) Str::uuid();
            }
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name);
            }
        });
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'server_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
