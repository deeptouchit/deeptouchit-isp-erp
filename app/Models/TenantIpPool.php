<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantIpPool extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'total_ips' => 'integer',
        'used_ips' => 'integer',
        'vlan_id' => 'integer',
        'is_sync_mikrotik' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    protected $appends = [
        'free_ips',
        'utilization_percent',
        'type_badge',
        'status_badge',
        'addresses_display',
        'comment',
    ];

    /**
     * Map comment to description
     */
    public function getCommentAttribute(): ?string
    {
        return $this->description;
    }

    public function setCommentAttribute(?string $value): void
    {
        $this->attributes['description'] = $value;
    }

    /**
     * Get remaining available free IPs
     */
    public function getFreeIpsAttribute(): int
    {
        return max(0, $this->total_ips - $this->used_ips);
    }

    /**
     * Get pool utilization percentage
     */
    public function getUtilizationPercentAttribute(): float
    {
        if ($this->total_ips <= 0) {
            return 0.0;
        }
        return round(($this->used_ips / $this->total_ips) * 100, 1);
    }

    /**
     * Get pool type visual badge styling
     */
    public function getTypeBadgeAttribute(): array
    {
        return match (strtolower($this->pool_type)) {
            'cgnat' => [
                'label' => 'CGNAT Block',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
            ],
            'static_public' => [
                'label' => 'Static Public',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
            'dhcp' => [
                'label' => 'DHCP Dynamic',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            ],
            'ipv6' => [
                'label' => 'IPv6 Prefix',
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            ],
            'vpn' => [
                'label' => 'VPN Pool',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ],
            default => [
                'label' => 'PPPoE Client',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            ],
        };
    }

    /**
     * Get pool status visual badge styling
     */
    public function getStatusBadgeAttribute(): array
    {
        if ($this->is_sync_mikrotik) {
            return [
                'label' => 'Synchronized',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        }

        return [
            'label' => 'Local Only',
            'class' => 'bg-slate-50 text-slate-600 border-slate-200',
        ];
    }

    /**
     * Get visual Addresses display string (matches MikroTik format)
     */
    public function getAddressesDisplayAttribute(): string
    {
        if (empty($this->range_start)) {
            return '-';
        }
        if ($this->range_start === $this->range_end || empty($this->range_end) || str_contains($this->range_start, '/')) {
            return $this->range_start;
        }
        return "{$this->range_start}-{$this->range_end}";
    }

    /**
     * Calculate total IPv4 count from range start and end
     */
    public static function calculateIpCount(string $start, string $end): int
    {
        if (str_contains($start, '/')) {
            $parts = explode('/', $start);
            $bits = (int) ($parts[1] ?? 32);
            return ($bits >= 0 && $bits <= 32) ? (int) pow(2, 32 - $bits) : 1;
        }

        $startLong = ip2long(trim($start));
        $endLong = ip2long(trim($end));

        if ($startLong === false || $endLong === false || $endLong < $startLong) {
            return 1;
        }

        return (int) ($endLong - $startLong + 1);
    }

    /**
     * Parse single MikroTik-style Addresses input (e.g. 10.10.0.0/22 or 10.10.0.2-10.10.3.254)
     */
    public static function parseRange(string $rangeString): array
    {
        $rangeString = trim($rangeString);
        if (empty($rangeString)) {
            return ['start' => '', 'end' => '', 'count' => 0];
        }

        // 1. CIDR notation e.g. 10.10.0.0/22 or 192.168.1.0/24
        if (str_contains($rangeString, '/')) {
            $parts = explode('/', $rangeString);
            $bits = (int) ($parts[1] ?? 24);
            $count = ($bits >= 0 && $bits <= 32) ? (int) pow(2, 32 - $bits) : 1;
            return [
                'start' => $rangeString,
                'end' => $rangeString,
                'count' => $count,
            ];
        }

        // 2. Hyphenated range e.g. 10.10.0.2-10.10.3.254
        if (str_contains($rangeString, '-')) {
            $parts = explode('-', $rangeString);
            $start = trim($parts[0] ?? '');
            $end = trim($parts[1] ?? $start);
            $count = self::calculateIpCount($start, $end);
            return [
                'start' => $start,
                'end' => $end,
                'count' => $count,
            ];
        }

        // 3. Single IP
        return [
            'start' => $rangeString,
            'end' => $rangeString,
            'count' => 1,
        ];
    }
}
