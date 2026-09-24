<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantInternetPackage extends Model
{
    use HasFactory;

    protected $table = 'tenant_internet_packages';

    /**
     * Exact fillable columns matching 2026_09_08_170000_create_tenant_internet_packages_table migration
     */
    protected $fillable = [
        'code',
        'service_type',
        'tenant_id',
        'router_id',
        'ip_pool_id',

        // MikroTik pppoe Profile Policies
        'name',
        'local_address',
        'remote_address',
        'only_one',
        'comment',

        // Pricing & Billing
        'package_name',
        'price',
        'wholesale_price',
        'min_retail_price',
        'allow_resellers',
        'validity_days',
        'validity_unit',
        'upload_speed',
        'download_speed',
        'facebook_speed',
        'youtube_speed',
        'bdix_speed',

        // MikroTik & RADIUS Mapping
        'mikrotik_profile',
        'address_list',
        'is_sync_mikrotik',
        'is_active',
        'subscribers_count',
        'description',
    ];

    /**
     * Type casts
     */
    protected $casts = [
        'price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'min_retail_price' => 'decimal:2',
        'allow_resellers' => 'boolean',
        'is_sync_mikrotik' => 'boolean',
        'is_active' => 'boolean',
        'subscribers_count' => 'integer',
    ];

    /**
     * Get effective wholesale price for a specific reseller based on their commission rate (or fallback 30%)
     */
    public function getEffectiveWholesalePrice(?TenantReseller $reseller = null): float
    {
        if ($reseller) {
            return $reseller->calculateResellerCost((float)$this->price);
        }
        return (float)($this->wholesale_price ?? ($this->price * 0.7));
    }

    /**
     * Tenant Relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Router Relationship
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    /**
     * IP Pool Relationship
     */
    public function ipPool(): BelongsTo
    {
        return $this->belongsTo(TenantIpPool::class, 'ip_pool_id');
    }

    /**
     * Customers subscribed to this package
     */
    public function customers(): HasMany
    {
        return $this->hasMany(TenantCustomer::class, 'package_id');
    }

    /**
     * Format download speed display
     */
    public function getDownloadSpeedFormattedAttribute(): string
    {
        if (!empty($this->download_speed)) {
            return is_numeric($this->download_speed) ? $this->download_speed . ' Mbps' : $this->download_speed;
        }
        return '10 Mbps';
    }

    /**
     * Format upload speed display
     */
    public function getUploadSpeedFormattedAttribute(): string
    {
        if (!empty($this->upload_speed)) {
            return is_numeric($this->upload_speed) ? $this->upload_speed . ' Mbps' : $this->upload_speed;
        }
        return '10 Mbps';
    }

    /**
     * Display name (prefers package_name for commercial display)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->package_name ?: $this->name;
    }

    /**
     * Rate limit string for MikroTik (e.g. 10M/10M)
     */
    public function getRateLimitFormattedAttribute(): string
    {
        $uploadNum = is_numeric($this->upload_speed) && (float)$this->upload_speed > 0 ? (float)$this->upload_speed : 10;
        $downloadNum = is_numeric($this->download_speed) && (float)$this->download_speed > 0 ? (float)$this->download_speed : 10;

        $rx = self::toMikrotikSpeed((int) ($uploadNum * 1024));
        $tx = self::toMikrotikSpeed((int) ($downloadNum * 1024));

        return "{$rx}/{$tx}";
    }

    /**
     * Convert Kbps to MikroTik speed shorthand (e.g. 10240 -> 10M, 512 -> 512k)
     */
    public static function toMikrotikSpeed(int $kbps): string
    {
        if ($kbps >= 1024 && ($kbps % 1024 === 0)) {
            return ($kbps / 1024) . 'M';
        }
        if ($kbps >= 1024) {
            return round($kbps / 1024, 1) . 'M';
        }
        return $kbps . 'k';
    }

    /**
     * Parse MikroTik speed shorthand to Kbps (e.g. 10M -> 10240, 512k -> 512)
     */
    public static function parseSpeedToKbps(string $speedStr): int
    {
        $speedStr = trim(strtoupper($speedStr));
        if (str_ends_with($speedStr, 'G')) {
            return (int) (floatval(substr($speedStr, 0, -1)) * 1048576);
        }
        if (str_ends_with($speedStr, 'M')) {
            return (int) (floatval(substr($speedStr, 0, -1)) * 1024);
        }
        if (str_ends_with($speedStr, 'K')) {
            return (int) floatval(substr($speedStr, 0, -1));
        }
        $val = (int) $speedStr;
        return $val > 0 ? (int) ($val / 1000) : 10240;
    }

    /**
     * Get Service Type Visual Badge styling
     */
    public function getServiceBadgeAttribute(): array
    {
        return match (strtolower($this->service_type ?? 'pppoe')) {
            'hotspot' => [
                'label' => 'Hotspot Plan',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-wifi',
            ],
            'static' => [
                'label' => 'Static / Dedicated',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-ethernet',
            ],
            'all' => [
                'label' => 'Universal Plan',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-globe',
            ],
            default => [
                'label' => 'PPPoE Client',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-network-wired',
            ],
        };
    }

    /**
     * Get Formatted Price (e.g. ৳800.00)
     */
    public function getFormattedPriceAttribute(): string
    {
        return '৳' . number_format($this->price ?? 0, 2);
    }

    /**
     * Get unique FreeRADIUS Group Name
     */
    public function getRadiusGroupNameAttribute(): string
    {
        return \App\Services\Network\RadiusService::getGroupName($this);
    }
}
