<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    protected $fillable = [
        'name',
        'subscription_id',
        'backup_storage_id',
        'frequency',
        'cron_expression',
        'type',
        'retention_count',
        'status',
        'last_run_at',
        'last_run_status',
        'next_run_at',
        'notify_on_failure',
    ];

    protected $casts = [
        'retention_count' => 'integer',
        'notify_on_failure' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function storage()
    {
        return $this->belongsTo(BackupStorage::class, 'backup_storage_id');
    }
}
