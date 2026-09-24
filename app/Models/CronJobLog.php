<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJobLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cron_job_id',
        'executed_at',
        'status',
        'duration_ms',
        'exit_code',
        'output',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'duration_ms' => 'integer',
        'exit_code' => 'integer',
    ];

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }
}
