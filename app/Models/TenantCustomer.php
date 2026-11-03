<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'monthly_bill' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'wallet_balance' => 'decimal:2',
        'auto_cut_enabled' => 'boolean',
        'grace_period_days' => 'integer',
        'billing_cycle_date' => 'date',
        'expiry_date' => 'date',
        'last_online_at' => 'datetime',
    ];

    public function getPppoeUsernameAttribute(): ?string
    {
        return $this->attributes['username'] ?? null;
    }

    public function getPppoePasswordAttribute(): ?string
    {
        return $this->attributes['password'] ?? null;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(TenantReseller::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TenantInternetPackage::class, 'package_id');
    }

    public function coverageZone(): BelongsTo
    {
        return $this->belongsTo(TenantCoverageZone::class, 'zone_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(TenantCoverageZone::class, 'zone_id');
    }

    /**
     * Package Display Name matching MikroTik profile name (e.g. 10Mbps, 25Mbps)
     */
    public function getPackageDisplayNameAttribute(): string
    {
        if ($this->package) {
            return $this->package->name ?: ($this->package->mikrotik_profile ?: ($this->package->package_name ?: 'Custom Package'));
        }
        return $this->package_name ?: 'Custom Package';
    }

    /**
     * Exact Profile Name provisioned on MikroTik router
     */
    public function getMikrotikProfileNameAttribute(): string
    {
        if ($this->package) {
            return $this->package->name ?: ($this->package->mikrotik_profile ?: ($this->package->package_name ?: 'default'));
        }
        return $this->package_name ?: 'default';
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(TenantRouter::class, 'router_id');
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(TenantOlt::class, 'olt_id');
    }

    /**
     * Resolve associated ONU optical device
     */
    public function getOnuDeviceAttribute(): ?TenantOnu
    {
        // 1. Link by Customer's Live Router MAC across ALL active OLT gateways
        $macToMatch = $this->mac_address;
        if (empty($macToMatch) && !empty($this->username)) {
            try {
                $session = \Illuminate\Support\Facades\DB::table('radacct')
                    ->where('username', $this->username)
                    ->whereNotNull('callingstationid')
                    ->where('callingstationid', '!=', '')
                    ->orderByDesc('radacctid')
                    ->first();
                if ($session && !empty($session->callingstationid)) {
                    $macToMatch = $session->callingstationid;
                    $this->updateQuietly(['mac_address' => $macToMatch]);
                }
            } catch (\Throwable $e) {
                // Ignore if radacct is inaccessible
            }
        }

        if (!empty($macToMatch)) {
            $routerMac = trim($macToMatch);
            $cleanRouterMac = strtolower(str_replace([':', '-', '.', ' '], '', $routerMac));

            $foundByRouterMac = TenantOnu::where('tenant_id', $this->tenant_id)
                ->where(function($q) use ($routerMac, $cleanRouterMac) {
                    $q->where('router_mac', $routerMac)
                      ->orWhere('mac_address', $routerMac)
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(LOWER(COALESCE(router_mac, '')), ':', ''), '-', ''), '.', '') = ?", [$cleanRouterMac])
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(LOWER(COALESCE(mac_address, '')), ':', ''), '-', ''), '.', '') = ?", [$cleanRouterMac]);
                })->first();

            if ($foundByRouterMac) {
                if ($foundByRouterMac->customer_id !== $this->id) {
                    TenantOnu::where('tenant_id', $this->tenant_id)
                        ->where('customer_id', $this->id)
                        ->where('id', '!=', $foundByRouterMac->id)
                        ->update(['customer_id' => null]);

                    $foundByRouterMac->updateQuietly([
                        'customer_id' => $this->id,
                        'router_mac' => $routerMac,
                        'pppoe_username' => $this->username,
                    ]);
                }
                if ($this->olt_id !== $foundByRouterMac->olt_id || empty($this->pon_port)) {
                    $this->updateQuietly([
                        'olt_id' => $foundByRouterMac->olt_id,
                        'pon_port' => $foundByRouterMac->pon_port . ($foundByRouterMac->onu_id ? ':' . $foundByRouterMac->onu_id : ''),
                        'onu_mac_sn' => $foundByRouterMac->mac_address ?: $this->onu_mac_sn,
                    ]);
                }
                return $foundByRouterMac;
            }
        }

        // 2. Direct customer_id link fallback
        $direct = TenantOnu::where('tenant_id', $this->tenant_id)->where('customer_id', $this->id)->first();
        if ($direct) {
            return $direct;
        }

        // 3. Link by ONU MAC or Serial Number (if set on customer)
        if (!empty($this->onu_mac_sn)) {
            $mac = trim($this->onu_mac_sn);
            $cleanMac = strtolower(str_replace([':', '-', '.', ' '], '', $mac));
            $found = TenantOnu::where('tenant_id', $this->tenant_id)
                ->where(function($q) use ($mac, $cleanMac) {
                    $q->where('mac_address', $mac)
                      ->orWhere('name', 'like', "%{$mac}%")
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(LOWER(COALESCE(mac_address, '')), ':', ''), '-', ''), '.', '') = ?", [$cleanMac]);
                })->first();
            if ($found) {
                if (empty($found->customer_id)) {
                    $found->updateQuietly(['customer_id' => $this->id]);
                }
                return $found;
            }
        }

        // 4. Link by PPPoE Username or Customer ID / Name in OLT description
        if (!empty($this->username)) {
            $found = TenantOnu::where('tenant_id', $this->tenant_id)
                ->where(function($q) {
                    $q->where('pppoe_username', $this->username)
                      ->orWhere('name', $this->username)
                      ->orWhere('desc', 'like', "%{$this->username}%");
                    if (!empty($this->customer_id)) {
                        $q->orWhere('name', 'like', "%{$this->customer_id}%")
                          ->orWhere('desc', 'like', "%{$this->customer_id}%");
                    }
                })
                ->first();
            if ($found) {
                if (empty($found->customer_id)) {
                    $found->updateQuietly([
                        'customer_id' => $this->id,
                        'pppoe_username' => $this->username,
                    ]);
                }
                return $found;
            }
        }

        return null;
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_collector_id');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantCustomerPayment::class, 'customer_id')->orderByDesc('id');
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantCustomerInvoice::class, 'customer_id')->orderByDesc('id');
    }

    /**
     * Dynamically resolved effective status (Active / Due / Expired / Suspended / Disabled)
     */
    public function getEffectiveStatusAttribute(): string
    {
        $rawStatus = strtolower($this->status ?? 'active');

        // Preserved explicit administrative states
        if (in_array($rawStatus, ['disabled', 'disconnected', 'archived'])) {
            return 'disabled';
        }

        if ($rawStatus === 'suspended') {
            return 'suspended';
        }

        // If expiry_date has passed, account is expired
        if ($this->expiry_date && $this->expiry_date->endOfDay()->isPast()) {
            return 'expired';
        }

        // If customer has outstanding dues
        if ($this->due_amount > 0 || $rawStatus === 'due') {
            return 'due';
        }

        return 'active';
    }

    /**
     * Check if customer is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->effective_status === 'expired' || ($this->expiry_date && $this->expiry_date->endOfDay()->isPast());
    }

    /**
     * Customer Status Visual Badge
     */
    public function getStatusBadgeAttribute(): array
    {
        $status = $this->effective_status;

        return match ($status) {
            'active' => [
                'label' => 'Active',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
                'icon' => 'fa-circle-check',
            ],
            'due' => [
                'label' => 'Due',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
                'icon' => 'fa-clock',
            ],
            'expired' => [
                'label' => 'Expired',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot' => 'bg-rose-500',
                'icon' => 'fa-triangle-exclamation',
            ],
            'suspended' => [
                'label' => 'Suspended',
                'class' => 'bg-red-50 text-red-700 border-red-200',
                'dot' => 'bg-red-500',
                'icon' => 'fa-ban',
            ],
            'disabled', 'disconnected', 'archived' => [
                'label' => 'Disabled',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot' => 'bg-slate-400',
                'icon' => 'fa-circle-pause',
            ],
            default => [
                'label' => ucfirst($status),
                'class' => 'bg-slate-50 text-slate-700 border-slate-200',
                'dot' => 'bg-slate-400',
                'icon' => 'fa-info-circle',
            ],
        };
    }

    /**
     * Expiry remaining or overdue days badge
     */
    public function getExpiryDaysBadgeAttribute(): array
    {
        if (!$this->expiry_date) {
            return [
                'days' => null,
                'state' => 'none',
                'label' => '--',
                'class' => 'text-slate-400 font-mono text-xs',
                'dot' => '',
            ];
        }

        $today = now()->startOfDay();
        $expiry = $this->expiry_date->copy()->startOfDay();
        $diff = (int)$today->diffInDays($expiry, false);

        if ($diff > 0) {
            $dayText = $diff . ' ' . ($diff === 1 ? 'Day' : 'Days') . ' Left';
            if ($diff <= 3) {
                return [
                    'days' => $diff,
                    'state' => 'expiring_soon',
                    'label' => $dayText,
                    'class' => 'bg-amber-50 text-amber-700 border-amber-200 shadow-2xs font-semibold',
                    'dot' => 'bg-amber-500 ring-2 ring-amber-200',
                ];
            }

            return [
                'days' => $diff,
                'state' => 'active',
                'label' => $dayText,
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 shadow-2xs font-semibold',
                'dot' => 'bg-emerald-500 ring-2 ring-emerald-200',
            ];
        }

        if ($diff === 0) {
            return [
                'days' => 0,
                'state' => 'today',
                'label' => 'Expires Today',
                'class' => 'bg-amber-50 text-amber-800 border-amber-300 shadow-2xs font-bold animate-pulse',
                'dot' => 'bg-amber-500 ring-2 ring-amber-300',
            ];
        }

        $overdueDays = abs($diff);
        $overdueText = $overdueDays . ' ' . ($overdueDays === 1 ? 'Day' : 'Days') . ' Over';

        return [
            'days' => $diff,
            'state' => 'overdue',
            'label' => $overdueText,
            'class' => 'bg-rose-50 text-rose-700 border-rose-200 shadow-2xs font-semibold',
            'dot' => 'bg-rose-500 ring-2 ring-rose-200',
        ];
    }

    /**
     * Scope Affiliation Badge (ISP Core Direct vs Sub-ISP Franchise)
     */
    public function getScopeBadgeAttribute(): array
    {
        if ($this->reseller_id && $this->reseller) {
            return [
                'scope' => 'RESELLER',
                'label' => $this->reseller->name,
                'sub_label' => 'Partner (' . $this->reseller->code . ')',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-handshake',
            ];
        }

        return [
            'scope' => 'ISP_DIRECT',
            'label' => 'ISP Direct Retail',
            'sub_label' => 'Direct Subscriber',
            'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'icon' => 'fa-building',
        ];
    }

    /**
     * Real-time Online Session Badge
     */
    public function getOnlineBadgeAttribute(): array
    {
        if ($this->online_status === 'online') {
            return [
                'status' => 'online',
                'label' => 'Online',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500 ring-2 ring-emerald-300 animate-pulse',
                'icon' => 'fa-signal',
            ];
        }

        return [
            'status' => 'offline',
            'label' => 'Offline',
            'class' => 'bg-slate-100 text-slate-600 border-slate-200',
            'dot' => 'bg-slate-400',
            'icon' => 'fa-power-off',
        ];
    }

    /**
     * Get Company Prefix for Customer ID
     */
    public static function getTenantPrefix(int $tenantId): string
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return 'ISP';
        }

        $name = trim($tenant->name ?: ($tenant->company_name ?: 'ISP'));
        // If 2 or more words (e.g. SpeedNet Online -> SO, SomitySoft ISP -> SI), take first letters
        $words = preg_split('/[\s\-_]+/', $name);
        if (count($words) >= 2) {
            $prefix = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $clean = preg_replace('/[^a-zA-Z]/', '', $name);
            $prefix = strtoupper(substr($clean, 0, 2));
        }

        return !empty($prefix) && ctype_alpha($prefix) ? $prefix : 'ISP';
    }

    /**
     * Auto Generate Guaranteed Unique Next Customer ID
     * Database Standard: 2-digit Year (e.g. 24, 25, 26) + 6-digit Sequence (e.g. 000940 -> 000941)
     */
    public static function generateNextCustomerId(int $tenantId): string
    {
        $currentYearPrefix = date('y'); // e.g. '26' for year 2026

        // Query the latest customer for this tenant
        $lastCustomer = self::where('tenant_id', $tenantId)
            ->whereNotNull('customer_id')
            ->where('customer_id', '!=', '')
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;

        if ($lastCustomer) {
            $lastCid = trim((string) $lastCustomer->customer_id);

            // Pattern 1: Standard 8-digit numeric (2-digit year + 6-digit sequence, e.g. 26000940)
            if (preg_match('/^(\d{2})(\d{6})$/', $lastCid, $matches)) {
                $nextSeq = ((int) $matches[2]) + 1;
            }
            // Pattern 2: Any purely numeric ID
            elseif (ctype_digit($lastCid)) {
                $val = (int) $lastCid;
                $nextSeq = (strlen($lastCid) >= 8) ? ($val % 1000000) + 1 : $val + 1;
            }
            // Pattern 3: Prefix with numbers (e.g. TD1001, SO1001)
            elseif (preg_match('/(\d+)$/', $lastCid, $matches)) {
                $nextSeq = ((int) $matches[1]) + 1;
            }
        }

        $nextCustomerId = $currentYearPrefix . str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);

        // Ensure absolute collision-free uniqueness in database
        while (self::where('tenant_id', $tenantId)->where('customer_id', $nextCustomerId)->exists()) {
            $nextSeq++;
            $nextCustomerId = $currentYearPrefix . str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
        }

        return $nextCustomerId;
    }
}
