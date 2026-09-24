<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupJob extends Model
{
    protected $fillable = [
        'subscription_id',
        'name',
        'type',
        'status',
        'file_path',
        'file_size',
        'remote_path',
        'storage_driver',
        'trigger_source',
        'started_at',
        'completed_at',
        'log_output',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
