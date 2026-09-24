<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantActivityLog extends Model
{
    use HasFactory;

    protected $table = 'tenant_activity_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Clean English Relative Time
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->locale('en')->diffForHumans() : 'N/A';
    }

    /**
     * Standard Date Time in English
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d-M-Y h:i:s A') : 'N/A';
    }

    /**
     * Actor Badge with Role Colors
     */
    public function getActorBadgeAttribute(): array
    {
        $actorType = strtolower($this->actor_type ?? 'system');

        return match ($actorType) {
            'owner', 'super_admin' => [
                'label' => 'ISP Super Admin',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-user-shield',
            ],
            'tenant', 'admin' => [
                'label' => 'ISP Admin',
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-user-tie',
            ],
            'staff', 'operator', 'technician' => [
                'label' => 'Operations Staff',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-user-gear',
            ],
            'reseller', 'sub_isp' => [
                'label' => 'Wholesale Reseller',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-handshake',
            ],
            'customer', 'subscriber' => [
                'label' => 'Customer Portal',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-user',
            ],
            default => [
                'label' => 'System Daemon',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-robot',
            ],
        };
    }

    /**
     * Event Type Badge with Icons and Styling
     */
    public function getEventTypeBadgeAttribute(): array
    {
        $event = strtoupper($this->event_type ?? 'SYSTEM_EVENT');

        if (str_contains($event, 'AUTH') || str_contains($event, 'LOGIN') || str_contains($event, 'LOGOUT') || str_contains($event, 'PASSWORD')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-key',
                'category' => 'Security & Auth',
            ];
        }

        if (str_contains($event, 'BILL') || str_contains($event, 'INVOICE') || str_contains($event, 'PAYMENT') || str_contains($event, 'RECHARGE') || str_contains($event, 'EXPENSE') || str_contains($event, 'WALLET')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-file-invoice-dollar',
                'category' => 'Billing & Finance',
            ];
        }

        if (str_contains($event, 'NETWORK') || str_contains($event, 'MIKROTIK') || str_contains($event, 'ROUTER') || str_contains($event, 'ONU') || str_contains($event, 'OLT') || str_contains($event, 'BANDWIDTH') || str_contains($event, 'RADIUS')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon' => 'fa-network-wired',
                'category' => 'Network & MikroTik',
            ];
        }

        if (str_contains($event, 'BACKUP') || str_contains($event, 'RESTORE') || str_contains($event, 'MAINTENANCE') || str_contains($event, 'CACHE') || str_contains($event, 'OPTIMIZE')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-box-archive',
                'category' => 'Backup & System',
            ];
        }

        if (str_contains($event, 'ROLE') || str_contains($event, 'PERMISSION') || str_contains($event, 'RBAC') || str_contains($event, 'SETTING') || str_contains($event, 'GATEWAY')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-sliders',
                'category' => 'Administration',
            ];
        }

        if (str_contains($event, 'CUSTOMER') || str_contains($event, 'SUBSCRIBER') || str_contains($event, 'ZONE')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'icon' => 'fa-users',
                'category' => 'Subscribers',
            ];
        }

        if (str_contains($event, 'TICKET') || str_contains($event, 'SUPPORT') || str_contains($event, 'JOB') || str_contains($event, 'INSTALLATION') || str_contains($event, 'ESCALATION')) {
            return [
                'label' => str_replace('_', ' ', $event),
                'class' => 'bg-teal-50 text-teal-700 border-teal-200',
                'icon' => 'fa-headset',
                'category' => 'Support & NOC',
            ];
        }

        return [
            'label' => str_replace('_', ' ', $event),
            'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            'icon' => 'fa-shield-halved',
            'category' => 'General Telemetry',
        ];
    }

    /**
     * Device & Browser Parsing
     */
    public function getDeviceAttribute(): array
    {
        $ua = $this->user_agent ?? '';
        
        $browser = 'Browser';
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($ua, 'Firefox')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) {
            $browser = 'Apple Safari';
        } elseif (str_contains($ua, 'Edg')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($ua, 'Postman') || str_contains($ua, 'Guzzle') || str_contains($ua, 'Symfony') || str_contains($ua, 'curl')) {
            $browser = 'API Client / Script';
        }

        $platform = 'Desktop';
        if (str_contains($ua, 'Windows')) {
            $platform = 'Windows OS';
        } elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) {
            $platform = 'macOS';
        } elseif (str_contains($ua, 'Linux')) {
            $platform = 'Linux';
        } elseif (str_contains($ua, 'Android')) {
            $platform = 'Android Mobile';
        } elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            $platform = 'iOS Device';
        }

        return [
            'browser' => $browser,
            'platform' => $platform,
            'is_script' => str_contains($ua, 'Symfony') || str_contains($ua, 'curl') || str_contains($ua, 'Guzzle'),
        ];
    }
}
