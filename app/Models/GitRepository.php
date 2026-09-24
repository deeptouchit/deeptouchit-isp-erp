<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GitRepository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'name',
        'repository_url',
        'branch',
        'deploy_path',
        'provider',
        'webhook_secret',
        'post_deploy_script',
        'auto_deploy',
        'status',
        'last_commit_hash',
        'last_commit_message',
        'last_commit_author',
        'last_deployed_at',
        'last_deployment_status',
    ];

    protected $casts = [
        'auto_deploy' => 'boolean',
        'last_deployed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($repo) {
            if (empty($repo->webhook_secret)) {
                $repo->webhook_secret = Str::random(32);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(GitDeploymentLog::class)->orderBy('deployed_at', 'desc');
    }
}
