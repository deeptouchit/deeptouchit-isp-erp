<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class TenantOlt extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'web_port' => 'integer',
        'snmp_port' => 'integer',
        'telnet_port' => 'integer',
        'ssh_port' => 'integer',
        'pon_ports_count' => 'integer',
        'total_pon_ports' => 'integer',
        'total_onus_count' => 'integer',
        'online_onus_count' => 'integer',
        'offline_onus_count' => 'integer',
        'los_onus_count' => 'integer',
        'cpu_load' => 'integer',
        'memory_usage' => 'integer',
        'temperature' => 'float',
        'license_limit' => 'integer',
        'license_time_hours' => 'integer',
        'license_auto_renew' => 'boolean',
        'license_renew_threshold_hours' => 'integer',
        'license_renew_days' => 'integer',
        'last_ping_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function getLicenseFormattedAttribute(): string
    {
        if ((int)$this->license_limit === 0) {
            return "Unlimited / Permanent";
        }
        $hours = (int)$this->license_time_hours;
        if ($hours <= 0) {
            return "Expired / Locked";
        }
        $days = floor($hours / 24);
        $remHours = $hours % 24;
        return sprintf("%02d days %02d hours", $days, $remHours);
    }

    public function getIsLicenseWarningAttribute(): bool
    {
        return (int)$this->license_limit === 1 && (int)$this->license_time_hours <= 168;
    }

    /**
     * Encrypt Web API password
     */
    public function setWebPasswordAttribute($value): void
    {
        $this->attributes['web_password'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt Web API password
     */
    public function getDecryptedWebPasswordAttribute(): ?string
    {
        try {
            return !empty($this->attributes['web_password']) ? Crypt::decryptString($this->attributes['web_password']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set SNMP community string as clean plaintext
     */
    public function setSnmpCommunityAttribute($value): void
    {
        $clean = !empty($value) ? trim($value) : 'public';
        // If an encrypted string was mistakenly passed in, unwrap it first
        while (is_string($clean) && str_starts_with($clean, 'eyJpdiI')) {
            try {
                $clean = Crypt::decryptString($clean);
            } catch (\Exception $e) {
                break;
            }
        }
        $this->attributes['snmp_community'] = $clean;
    }

    /**
     * Get SNMP community string
     */
    public function getSnmpCommunityAttribute($value): ?string
    {
        if (empty($value)) {
            return 'public';
        }
        $val = $value;
        while (is_string($val) && str_starts_with($val, 'eyJpdiI')) {
            try {
                $val = Crypt::decryptString($val);
            } catch (\Exception $e) {
                break;
            }
        }
        return $val;
    }

    /**
     * Decrypt SNMP community string (Backward compatibility)
     */
    public function getDecryptedSnmpCommunityAttribute(): ?string
    {
        return $this->snmp_community;
    }

    /**
     * Encrypt Telnet password
     */
    public function setTelnetPasswordAttribute($value): void
    {
        $this->attributes['telnet_password'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt Telnet password
     */
    public function getDecryptedTelnetPasswordAttribute(): ?string
    {
        try {
            return !empty($this->attributes['telnet_password']) ? Crypt::decryptString($this->attributes['telnet_password']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    public function onus(): HasMany
    {
        return $this->hasMany(TenantOnu::class, 'olt_id');
    }
}
