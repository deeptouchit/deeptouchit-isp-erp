<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Support\ServerHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display a comprehensive list of active hosting subscriptions with quota analytics.
     */
    public function index(): Response
    {
        $userId = auth()->id();

        $subscriptions = Subscription::where('user_id', $userId)
            ->with(['plan', 'websites', 'databases', 'emailAccounts', 'ftpAccounts'])
            ->latest()
            ->get()
            ->map(function ($sub) {
                $daysRemaining = $sub->expires_at ? max(0, (int) now()->diffInDays($sub->expires_at, false)) : null;

                return [
                    'id' => $sub->id,
                    'domain' => $sub->domain,
                    'username' => $sub->username,
                    'document_root' => $sub->document_root,
                    'php_version' => $sub->php_version ?? '8.2',
                    'status' => $sub->status ?? 'active',
                    'period' => $sub->period ?? 'monthly',
                    'price' => (float) ($sub->price ?? 0),
                    'next_billing_date' => $sub->next_billing_date?->format('M d, Y'),
                    'expires_at' => $sub->expires_at?->format('M d, Y'),
                    'days_remaining' => $daysRemaining,
                    'is_expiring_soon' => $daysRemaining !== null && $daysRemaining <= 7,
                    'plan' => [
                        'id' => $sub->plan?->id,
                        'name' => $sub->plan?->name ?? 'Custom Plan',
                        'slug' => $sub->plan?->slug,
                        'description' => $sub->plan?->description,
                        'disk_space_mb' => $sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048),
                        'disk_space_formatted' => ($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048)) >= 1024 
                            ? round(($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048)) / 1024, 1) . ' GB' 
                            : ($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 1024)) . ' MB',
                        'bandwidth_formatted' => ($sub->plan?->bandwidth ?? 51200) >= 1024 
                            ? round(($sub->plan?->bandwidth ?? 51200) / 1024, 1) . ' GB' 
                            : ($sub->plan?->bandwidth ?? 51200) . ' MB',
                        'max_domains' => (int) ($sub->plan?->max_domains ?? 1),
                        'max_databases' => (int) ($sub->plan?->max_databases ?? 1),
                        'max_email_accounts' => (int) ($sub->plan?->max_email_accounts ?? 1),
                        'max_ftp_accounts' => (int) ($sub->plan?->max_ftp_accounts ?? 1),
                        'ram_limit' => $sub->plan?->ram_limit ?? 768,
                        'cpu_limit' => $sub->plan?->cpu_limit ?? 100,
                        'allow_redis' => (bool) ($sub->plan?->allow_redis ?? false),
                        'redis_memory_mb' => (int) ($sub->plan?->redis_memory_mb ?: 64),
                        'allow_memcached' => (bool) ($sub->plan?->allow_memcached ?? false),
                        'allow_nodejs' => (bool) ($sub->plan?->allow_nodejs ?? false),
                        'allow_python' => (bool) ($sub->plan?->allow_python ?? false),
                        'allow_cron_jobs' => (bool) ($sub->plan?->allow_cron_jobs ?? true),
                        'allow_backups' => (bool) ($sub->plan?->allow_backups ?? true),
                        'allow_ssh_access' => (bool) ($sub->plan?->allow_ssh_access ?? false),
                        'allow_git_deploy' => (bool) ($sub->plan?->allow_git_deploy ?? false),
                    ],
                    'usage' => [
                        'websites_count' => $sub->websites->count(),
                        'databases_count' => $sub->databases->count(),
                        'email_accounts_count' => $sub->emailAccounts->count(),
                        'ftp_accounts_count' => $sub->ftpAccounts->count(),
                    ],
                    'hosting_details' => [
                        'disk_space' => ($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048)) >= 1024 
                            ? round(($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048)) / 1024, 1) . ' GB NVMe SSD' 
                            : ($sub->custom_disk_space ?: ($sub->plan?->disk_space ?? 2048)) . ' MB SSD',
                        'ram' => (($sub->plan?->ram_limit ?? 768) >= 1024 ? round(($sub->plan?->ram_limit ?? 768) / 1024, 1) . ' GB' : ($sub->plan?->ram_limit ?? 768) . ' MB') . ' RAM',
                        'cpu_cores' => '1 Core (' . ($sub->plan?->cpu_limit ?? 100) . '% Limit)',
                        'inodes' => number_format($sub->custom_inodes ?: 150000) . ' Files',
                        'addons_websites' => $sub->websites->count() . ' / ' . ($sub->plan?->max_domains ?? 1) . ' Allowed',
                        'max_processes' => '25 Concurrent (NPROC)',
                        'php_workers' => '5 FPM Workers',
                        'bandwidth' => ($sub->plan?->bandwidth ?? 51200) >= 1024 
                            ? round(($sub->plan?->bandwidth ?? 51200) / 1024, 1) . ' GB (BDIX)' 
                            : '50 GB (BDIX)',
                    ],
                    'server_details' => [
                        'server_name' => 'BDIX-Node-01 (deeptouchcloud)',
                        'server_location' => 'Dhaka, Bangladesh (BDIX Tier-III DC)',
                        'backups_location' => 'Local NVMe + Offsite S3 Mirror',
                        'server_ip' => ServerHelper::getPublicIp(),
                        'primary_ns' => 'ns1.deeptouchit.com',
                        'secondary_ns' => 'ns2.deeptouchit.com',
                    ],
                ];
            });

        // Compute summary metrics
        $totalMonthly = $subscriptions->where('period', 'monthly')->sum('price');
        $totalYearly = $subscriptions->where('period', 'yearly')->sum('price');
        $nextExpiry = $subscriptions->sortBy('expires_at')->first()['expires_at'] ?? null;

        $metrics = [
            'total_active' => $subscriptions->where('status', 'active')->count(),
            'monthly_commitment' => $totalMonthly,
            'yearly_commitment' => $totalYearly,
            'next_renewal' => $nextExpiry,
        ];

        $serverInfo = [
            'public_ip' => ServerHelper::getPublicIp(),
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
        ];

        return Inertia::render('Client/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'metrics' => $metrics,
            'serverInfo' => $serverInfo,
        ]);
    }

    /**
     * Show detailed technical view of a specific subscription.
     */
    public function show(Subscription $subscription): Response
    {
        if ($subscription->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $subscription->load([
            'plan',
            'websites',
            'databases',
            'emailAccounts',
            'ftpAccounts',
            'invoices' => fn($q) => $q->latest()->take(5),
        ]);

        $daysRemaining = $subscription->expires_at ? max(0, (int) now()->diffInDays($subscription->expires_at, false)) : null;

        $serverInfo = [
            'public_ip' => ServerHelper::getPublicIp(),
            'primary_ns' => 'ns1.deeptouchit.com',
            'secondary_ns' => 'ns2.deeptouchit.com',
        ];

        return Inertia::render('Client/Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'domain' => $subscription->domain,
                'username' => $subscription->username,
                'document_root' => $subscription->document_root,
                'php_version' => $subscription->php_version ?? '8.2',
                'status' => $subscription->status ?? 'active',
                'period' => $subscription->period ?? 'monthly',
                'price' => (float) ($subscription->price ?? 0),
                'next_billing_date' => $subscription->next_billing_date?->format('M d, Y'),
                'expires_at' => $subscription->expires_at?->format('M d, Y'),
                'days_remaining' => $daysRemaining,
                'plan' => $subscription->plan,
                'websites' => $subscription->websites,
                'databases' => $subscription->databases,
                'email_accounts' => $subscription->emailAccounts,
                'ftp_accounts' => $subscription->ftpAccounts,
                'invoices' => $subscription->invoices,
            ],
            'serverInfo' => $serverInfo,
        ]);
    }
}
