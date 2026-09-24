<?php

namespace App\Models;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Traits\AuditLoggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory, SoftDeletes, AuditLoggable;

    protected $fillable = [
        'uuid',
        'server_group_id',
        'name',
        'hostname',
        'ip_address',
        'primary_ip',
        'ipv6',
        'server_type',
        'environment',
        'health_status',
        'status',
        'is_master',
        'os',
        'os_name',
        'os_version',
        'kernel_version',
        'architecture',
        'cpu_cores',
        'total_ram',
        'total_disk',
        'used_ram',
        'used_disk',
        'load_avg_1min',
        'load_avg_5min',
        'load_avg_15min',
        'ssh_port',
        'ssh_user',
        'auth_type',
        'encrypted_ssh_key',
        'encrypted_ssh_password',
        'ssh_host_key_fingerprint',
        'trusted_ssh_host_key_fingerprint',
        'ssh_host_key_policy',
        'agent_token',
        'agent_version',
        'agent_installed_at',
        'last_ping_at',
        'last_seen_at',
        'last_health_check_at',
        'maintenance_at',
        'maintenance_reason',
        'decommissioned_at',
    ];

    protected $hidden = [
        'encrypted_ssh_key',
        'encrypted_ssh_password',
        'agent_token',
    ];

    protected $casts = [
        'status' => ServerStatus::class,
        'health_status' => ServerHealthStatus::class,
        'server_type' => ServerType::class,
        'environment' => ServerEnvironment::class,
        'auth_type' => ServerAuthType::class,
        'is_master' => 'boolean',
        'encrypted_ssh_key' => 'encrypted',
        'encrypted_ssh_password' => 'encrypted',
        'cpu_cores' => 'integer',
        'total_ram' => 'integer',
        'total_disk' => 'integer',
        'used_ram' => 'integer',
        'used_disk' => 'integer',
        'load_avg_1min' => 'float',
        'load_avg_5min' => 'float',
        'load_avg_15min' => 'float',
        'last_ping_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'maintenance_at' => 'datetime',
        'agent_installed_at' => 'datetime',
        'decommissioned_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($server) {
            if (empty($server->uuid)) {
                $server->uuid = (string) Str::uuid();
            }
            if (empty($server->primary_ip) && !empty($server->ip_address)) {
                $server->primary_ip = $server->ip_address;
            }
        });
    }

    // Relationships
    public function group(): BelongsTo
    {
        return $this->belongsTo(ServerGroup::class, 'server_group_id');
    }

    public function serverGroup(): BelongsTo
    {
        return $this->belongsTo(ServerGroup::class, 'server_group_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ServerMetric::class);
    }

    public function latestMetric(): HasOne
    {
        return $this->hasOne(ServerMetric::class)->latestOfMany('recorded_at');
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServerService::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ServerLog::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ServerEvent::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ServerCredential::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function alertStates(): HasMany
    {
        return $this->hasMany(MonitoringAlertState::class);
    }

    public function metricAggregates(): HasMany
    {
        return $this->hasMany(ServerMetricAggregate::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [ServerStatus::ACTIVE, ServerStatus::ONLINE, ServerStatus::VERIFIED]);
    }

    public function scopeOnline($query)
    {
        return $query->whereIn('status', [ServerStatus::ACTIVE, ServerStatus::ONLINE]);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', ServerStatus::OFFLINE);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', ServerStatus::MAINTENANCE);
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_status', ServerHealthStatus::HEALTHY);
    }

    public function scopeCritical($query)
    {
        return $query->where('health_status', ServerHealthStatus::CRITICAL);
    }

    public function scopeMaster($query)
    {
        return $query->where('is_master', true);
    }

    public function scopeWorkers($query)
    {
        return $query->where('is_master', false);
    }

    public function scopeProduction($query)
    {
        return $query->where('environment', ServerEnvironment::PRODUCTION);
    }
}
