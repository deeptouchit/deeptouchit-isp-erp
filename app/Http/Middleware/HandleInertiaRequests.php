<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'is_impersonating' => $request->session()->has('impersonated_by'),
            ],
            'server_public_ip' => \App\Support\ServerHelper::getPublicIp(),
            'server_private_ip' => \App\Support\ServerHelper::getPrivateIp(),
            'app_name' => \App\Models\SystemSetting::get('branding.brand_name') 
                ?: (\App\Models\SystemSetting::get('general.app_name') ?: config('app.name', 'DeepTouchHost')),
            'app_tagline' => \App\Models\SystemSetting::get('branding.tagline', 'Enterprise Cloud Engine'),
            'app_logo' => \App\Models\SystemSetting::get('branding.logo_light_url'),
            'support_phone' => \App\Models\SystemSetting::get('general.support_phone', '+880 1977-799300'),
            'support_email' => \App\Models\SystemSetting::get('general.support_email', 'support@deeptouchit.com'),
            'support_whatsapp' => \App\Models\SystemSetting::get('general.whatsapp_number', '8801977799300'),
            'copyright_text' => \App\Models\SystemSetting::get('branding.copyright_text') 
                ?: ('© ' . date('Y') . ' ' . (\App\Models\SystemSetting::get('general.company_name') ?: 'DeepTouch IT Ltd.') . ' All rights reserved.'),
            'help_url' => \App\Models\SystemSetting::get('branding.help_url', 'https://help.deeptouchit.com'),
            'hosting_plans' => \App\Models\HostingPlan::where('is_active', true)->orderBy('sort_order')->orderBy('price_monthly')->get(),
            'panel_version' => 'v2.4.0-PRO',
            'server_time' => now()->format('H:i T'),
            'admin_notifications' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') {
                    return [];
                }

                $notifications = [];

                // 1. Open / Waiting Support Tickets
                try {
                    $tickets = \App\Models\SupportTicket::with('user:id,first_name,last_name,username,email')
                        ->whereIn('status', ['open', 'waiting'])
                        ->latest('updated_at')
                        ->take(6)
                        ->get();

                    foreach ($tickets as $tkt) {
                        $clientName = $tkt->user 
                            ? (trim(($tkt->user->first_name ?? '') . ' ' . ($tkt->user->last_name ?? '')) ?: ($tkt->user->username ?: $tkt->user->email))
                            : 'Client';
                        $notifications[] = [
                            'id' => 'tkt_' . $tkt->id,
                            'type' => 'ticket',
                            'title' => "Support Ticket #{$tkt->ticket_no}",
                            'desc' => "{$clientName}: {$tkt->subject}",
                            'priority' => $tkt->priority,
                            'status' => $tkt->status,
                            'department' => $tkt->department,
                            'time' => $tkt->updated_at ? $tkt->updated_at->diffForHumans() : 'Just now',
                            'url' => route('admin.tickets.show', $tkt->id),
                        ];
                    }
                } catch (\Throwable $e) {
                    // Ignore if error
                }

                // 2. Unpaid Invoices
                try {
                    $invoices = \App\Models\Invoice::with('user:id,first_name,last_name,username,email')
                        ->where('status', 'unpaid')
                        ->latest()
                        ->take(3)
                        ->get();

                    foreach ($invoices as $inv) {
                        $clientName = $inv->user 
                            ? (trim(($inv->user->first_name ?? '') . ' ' . ($inv->user->last_name ?? '')) ?: ($inv->user->username ?: $inv->user->email))
                            : 'Client';
                        $notifications[] = [
                            'id' => 'inv_' . $inv->id,
                            'type' => 'invoice',
                            'title' => "Unpaid Invoice #{$inv->invoice_no}",
                            'desc' => "{$clientName} • ৳" . number_format($inv->total_amount, 2),
                            'priority' => 'normal',
                            'status' => 'unpaid',
                            'department' => 'billing',
                            'time' => $inv->created_at ? $inv->created_at->diffForHumans() : 'Just now',
                        ];
                    }
                } catch (\Throwable $e) {
                    // Ignore if error
                }

                return $notifications;
            },
            'admin_unread_ticket_count' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'admin') {
                    return 0;
                }
                try {
                    return \App\Models\SupportTicket::whereIn('status', ['open', 'waiting'])->count();
                } catch (\Throwable $e) {
                    return 0;
                }
            },
            'client_notifications' => function () use ($request) {
                if (!$request->user() || $request->user()->role === 'admin') {
                    return [];
                }
                $userId = $request->user()->id;
                $notifications = [];

                // 1. Answered Support Tickets
                try {
                    $tickets = \App\Models\SupportTicket::where('user_id', $userId)
                        ->whereIn('status', ['answered', 'in_progress'])
                        ->latest('updated_at')
                        ->take(5)
                        ->get();

                    foreach ($tickets as $tkt) {
                        $notifications[] = [
                            'id' => 'tkt_' . $tkt->id,
                            'type' => 'ticket',
                            'title' => "Support Ticket #{$tkt->ticket_no}",
                            'desc' => "Staff response: {$tkt->subject}",
                            'status' => $tkt->status,
                            'time' => $tkt->updated_at ? $tkt->updated_at->diffForHumans() : 'Just now',
                            'url' => route('tickets.show', $tkt->id),
                        ];
                    }
                } catch (\Throwable $e) {}

                // 2. Unpaid Invoices
                try {
                    $invoices = \App\Models\Invoice::where('user_id', $userId)
                        ->where('status', 'sent')
                        ->latest('due_date')
                        ->take(3)
                        ->get();

                    foreach ($invoices as $inv) {
                        $notifications[] = [
                            'id' => 'inv_' . $inv->id,
                            'type' => 'invoice',
                            'title' => "Unpaid Invoice #{$inv->invoice_no}",
                            'desc' => "Due: ৳" . number_format($inv->due_amount ?: $inv->total_amount, 2),
                            'status' => 'unpaid',
                            'time' => $inv->due_date ? 'Due ' . $inv->due_date->diffForHumans() : 'Pending',
                            'url' => route('billing.invoice.show', $inv->id),
                        ];
                    }
                } catch (\Throwable $e) {}

                return $notifications;
            },
            'client_unread_count' => function () use ($request) {
                if (!$request->user() || $request->user()->role === 'admin') {
                    return 0;
                }
                $userId = $request->user()->id;
                try {
                    $tkts = \App\Models\SupportTicket::where('user_id', $userId)->where('status', 'answered')->count();
                    $invs = \App\Models\Invoice::where('user_id', $userId)->where('status', 'sent')->count();
                    return $tkts + $invs;
                } catch (\Throwable $e) {
                    return 0;
                }
            },
            'currentSubscription' => function () use ($request) {
                if (!$request->user() || $request->user()->role === 'admin') {
                    return null;
                }
                return \App\Models\Subscription::with('plan', 'server')
                    ->where('user_id', $request->user()->id)
                    ->whereIn('status', ['active', 'trialing'])
                    ->latest()
                    ->first()
                    ?: \App\Models\Subscription::with('plan', 'server')
                        ->where('user_id', $request->user()->id)
                        ->latest()
                        ->first();
            },
            'client_plan_features' => function () use ($request) {
                if (!$request->user() || $request->user()->role === 'admin') {
                    return null;
                }
                try {
                    $subscription = \App\Models\Subscription::with('plan')
                        ->where('user_id', $request->user()->id)
                        ->whereIn('status', ['active', 'trialing'])
                        ->latest()
                        ->first();
                    if (!$subscription || !$subscription->plan) {
                        return null;
                    }
                    $p = $subscription->plan;
                    return [
                        'plan_name' => $p->name,
                        'allow_redis' => (bool) $p->allow_redis,
                        'redis_memory_mb' => (int) ($p->redis_memory_mb ?: 64),
                        'allow_memcached' => (bool) $p->allow_memcached,
                        'allow_nodejs' => (bool) $p->allow_nodejs,
                        'allow_python' => (bool) $p->allow_python,
                        'allow_ssh_access' => (bool) $p->allow_ssh_access,
                        'allow_git_deploy' => (bool) $p->allow_git_deploy,
                        'allow_custom_php_ini' => (bool) $p->allow_custom_php_ini,
                        'allow_cron_jobs' => (bool) $p->allow_cron_jobs,
                        'allow_backups' => (bool) $p->allow_backups,
                        'auto_ssl' => (bool) $p->auto_ssl,
                    ];
                } catch (\Throwable $e) {
                    return null;
                }
            },
        ];
    }
}
