<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitDeploymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'git_repository_id',
        'trigger_type',
        'commit_hash',
        'commit_message',
        'commit_author',
        'status',
        'duration_ms',
        'exit_code',
        'output',
        'deployed_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'exit_code' => 'integer',
        'deployed_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitRepository::class, 'git_repository_id');
    }
}
