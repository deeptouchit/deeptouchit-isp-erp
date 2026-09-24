<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TenantRouter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $appends = [
        'decrypted_radius_secret',
        'connection_badge',
    ];

    protected $casts = [
        'use_ssl' => 'boolean',
        'is_active' => 'boolean',
        'cpu_load' => 'integer',
        'free_memory' => 'integer',
        'total_memory' => 'integer',
        'last_ping_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Set the router API password (encrypted in vault)
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted API password
     */
    public function getDecryptedPasswordAttribute(): ?string
    {
        try {
            return !empty($this->attributes['password']) ? Crypt::decryptString($this->attributes['password']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set RADIUS Secret (encrypted in vault)
     */
    public function setRadiusSecretAttribute($value): void
    {
        $this->attributes['radius_secret'] = !empty($value) ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted RADIUS Secret
     */
    public function getDecryptedRadiusSecretAttribute(): ?string
    {
        try {
            return !empty($this->attributes['radius_secret']) ? Crypt::decryptString($this->attributes['radius_secret']) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate ready-to-run MikroTik CLI configuration script for FreeRADIUS.
     */
    public function generateMikrotikRadiusScript(?string $serverIp = null): string
    {
        $serverIp = $serverIp ?: (request()->getHost() ?: '103.59.177.136');
        if (in_array($serverIp, ['127.0.0.1', 'localhost', '10.70.0.1', '10.70.0.2'])) {
            $serverIp = '103.59.177.136';
        }
        $secret = $this->decrypted_radius_secret ?: ($this->decrypted_password ?: 'radius@123');
        $name = addslashes($this->name);

        return <<<SCRIPT
# ====================================================================
# FreeRADIUS Client Setup for MikroTik Router: {$name}
# Platform: SomitySoft SaaS ISP AAA Engine
# ====================================================================

# 1. Add FreeRADIUS Server
/radius add service=ppp,hotspot,login address={$serverIp} secret="{$secret}" authentication-port=1812 accounting-port=1813 timeout=3000ms comment="SomitySoft FreeRADIUS Server"

# 2. Enable RADIUS Incoming CoA / Disconnect (Port 3799)
/radius incoming set accept=yes port=3799

# 3. Configure PPPoE AAA to authenticate via FreeRADIUS
/ppp aaa set use-radius=yes accounting=yes interim-update=5m

# 4. Optional: Configure Hotspot AAA (if using Hotspot)
/ip hotspot profile set [ find default=yes ] use-radius=yes radius-interim-update=5m
SCRIPT;
    }

    /**
     * Get Connection Type Visual Badge
     */
    public function getConnectionBadgeAttribute(): array
    {
        return match (strtolower($this->connection_type ?: 'radius')) {
            'api' => [
                'label' => 'API ONLY',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-code',
            ],
            'hybrid' => [
                'label' => 'HYBRID (RADIUS+API)',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-layer-group',
            ],
            default => [
                'label' => 'RADIUS AAA',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-shield-halved',
            ],
        };
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function olts()
    {
        return $this->hasMany(TenantOlt::class, 'router_id');
    }

    public function nasClients()
    {
        return $this->hasMany(TenantNas::class, 'router_id');
    }

    /**
     * Get professional human-readable formatted uptime string.
     */
    public function getFormattedUptimeAttribute(): string
    {
        if (empty($this->uptime)) {
            return '--';
        }

        $raw = trim($this->uptime);

        // Parse MikroTik uptime format (e.g. 1w2d3h4m5s, 1d7h34m25s, 4h30m)
        preg_match_all('/(\d+)\s*([wdhms])/i', $raw, $matches, PREG_SET_ORDER);
        if (!empty($matches)) {
            $parts = [];
            foreach ($matches as $match) {
                $val = (int)$match[1];
                $unit = strtolower($match[2]);
                if ($unit === 'w') $parts[] = "{$val}w";
                elseif ($unit === 'd') $parts[] = "{$val}d";
                elseif ($unit === 'h') $parts[] = "{$val}h";
                elseif ($unit === 'm') $parts[] = "{$val}m";
                elseif ($unit === 's' && count($parts) < 2) $parts[] = "{$val}s";
            }
            return !empty($parts) ? implode(' ', array_slice($parts, 0, 3)) : $raw;
        }

        // HH:MM:SS format (e.g. 14:20:00)
        if (preg_match('/^(\d+):(\d{2}):(\d{2})$/', $raw, $timeMatches)) {
            $h = (int)$timeMatches[1];
            $m = (int)$timeMatches[2];
            $d = (int)floor($h / 24);
            $remH = $h % 24;
            if ($d > 0) {
                return "{$d}d {$remH}h {$m}m";
            }
            return "{$h}h {$m}m";
        }

        return $raw;
    }
}
