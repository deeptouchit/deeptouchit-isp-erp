<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to write immutable network log
     */
    public static function record(
        string $action,
        string $deviceType,
        ?string $deviceName = null,
        string $status = 'success',
        ?string $command = null,
        ?string $summary = null,
        ?int $tenantId = null,
        ?int $deviceId = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'device_type' => $deviceType,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'action' => $action,
            'status' => $status,
            'command_executed' => $command,
            'response_summary' => $summary,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
