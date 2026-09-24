<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CronJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'command',
        'cron_expression',
        'description',
        'output_handling',
        'log_file_path',
        'is_enabled',
        'run_as_user',
        'last_run_at',
        'last_run_status',
        'last_run_duration_ms',
        'last_output_preview',
        'next_run_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'last_run_duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CronJobLog::class)->orderBy('executed_at', 'desc');
    }

    /**
     * Get human-readable description of the cron schedule.
     */
    public function getScheduleHumanAttribute(): string
    {
        $expr = trim($this->cron_expression);

        return match ($expr) {
            '* * * * *' => 'Every minute',
            '*/2 * * * *' => 'Every 2 minutes',
            '*/5 * * * *' => 'Every 5 minutes',
            '*/10 * * * *' => 'Every 10 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Hourly (at minute 0)',
            '0 0 * * *' => 'Daily at Midnight (00:00)',
            '0 2 * * *' => 'Daily at 02:00 AM',
            '0 0 * * 0' => 'Weekly (Sunday at 00:00)',
            '0 0 1 * *' => 'Monthly (1st day at 00:00)',
            default => "Custom ({$expr})",
        };
    }
}
