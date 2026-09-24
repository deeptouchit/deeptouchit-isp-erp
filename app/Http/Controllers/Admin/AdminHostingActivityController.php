<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminHostingActivityController extends Controller
{
    /**
     * Display directory of hosting workload events, provisioning logs, and runtime telemetry.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $hostingActions = [
            'hosting_account_created',
            'hosting_account_suspended',
            'hosting_account_unsuspended',
            'hosting_plan_changed',
            'hosting_php_changed',
            'hosting_account_deleted',
            'domain_vhost_created',
            'domain_ssl_issued',
            'domain_php_changed',
            'domain_status_toggled',
            'domain_vhost_deleted',
            'subdomain_vhost_created',
            'subdomain_ssl_issued',
            'subdomain_php_changed',
            'subdomain_status_toggled',
            'subdomain_vhost_deleted',
            'domain_alias_created',
            'domain_alias_ssl_issued',
            'domain_alias_status_toggled',
            'domain_alias_deleted',
            'plan_created',
            'plan_updated',
            'plan_deleted',
            'plan_status_toggled',
        ];

        $query = ActivityLog::query()
            ->with(['user:id,first_name,last_name,username,email,role'])
            ->where(function ($q) use ($hostingActions) {
                $q->whereIn('action', $hostingActions)
                    ->orWhere('action', 'like', 'hosting_%')
                    ->orWhere('action', 'like', 'domain_%')
                    ->orWhere('action', 'like', 'subdomain_%')
                    ->orWhere('action', 'like', 'plan_%');
            });

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Action Category Filter
        if ($actionFilter = $request->input('action_type')) {
            if ($actionFilter === 'provisioning') {
                $query->whereIn('action', [
                    'hosting_account_created', 'domain_vhost_created', 
                    'subdomain_vhost_created', 'domain_alias_created', 'plan_created'
                ]);
            } elseif ($actionFilter === 'security') {
                $query->whereIn('action', [
                    'domain_ssl_issued', 'subdomain_ssl_issued', 'domain_alias_ssl_issued',
                    'hosting_account_suspended', 'domain_status_toggled', 'subdomain_status_toggled'
                ]);
            } elseif ($actionFilter === 'runtime') {
                $query->whereIn('action', [
                    'hosting_php_changed', 'domain_php_changed', 
                    'subdomain_php_changed', 'hosting_plan_changed'
                ]);
            } elseif ($actionFilter === 'termination') {
                $query->whereIn('action', [
                    'hosting_account_deleted', 'domain_vhost_deleted', 
                    'subdomain_vhost_deleted', 'domain_alias_deleted', 'plan_deleted'
                ]);
            } else {
                $query->where('action', $actionFilter);
            }
        }

        // Date Range Filter
        if ($dateRange = $request->input('date_range')) {
            if ($dateRange === 'today') {
                $query->whereDate('created_at', today());
            } elseif ($dateRange === '7d') {
                $query->where('created_at', '>=', now()->subDays(7));
            } elseif ($dateRange === '30d') {
                $query->where('created_at', '>=', now()->subDays(30));
            }
        }

        $activities = $query->latest('created_at')->paginate(20)->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $baseStatsQuery = ActivityLog::where(function ($q) use ($hostingActions) {
            $q->whereIn('action', $hostingActions)
                ->orWhere('action', 'like', 'hosting_%')
                ->orWhere('action', 'like', 'domain_%')
                ->orWhere('action', 'like', 'subdomain_%')
                ->orWhere('action', 'like', 'plan_%');
        });

        $allLogs = $baseStatsQuery->get();

        $provisioningOps = $allLogs->filter(function ($log) {
            return in_array($log->action, [
                'hosting_account_created', 'domain_vhost_created', 
                'subdomain_vhost_created', 'domain_alias_created', 'plan_created'
            ]);
        })->count();

        $securityOps = $allLogs->filter(function ($log) {
            return in_array($log->action, [
                'domain_ssl_issued', 'subdomain_ssl_issued', 'domain_alias_ssl_issued',
                'hosting_account_suspended', 'domain_status_toggled', 'subdomain_status_toggled'
            ]);
        })->count();

        $runtimeOps = $allLogs->filter(function ($log) {
            return in_array($log->action, [
                'hosting_php_changed', 'domain_php_changed', 
                'subdomain_php_changed', 'hosting_plan_changed'
            ]);
        })->count();

        $stats = [
            'total_events' => $allLogs->count(),
            'provisioning_ops' => $provisioningOps,
            'security_ops' => $securityOps,
            'runtime_ops' => $runtimeOps,
        ];

        return Inertia::render('Admin/Hosting/Activity/Index', [
            'activities' => $activities,
            'stats' => $stats,
            'filters' => $request->only(['search', 'action_type', 'date_range']),
        ]);
    }

    /**
     * Export hosting activity audit stream to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $hostingActions = [
            'hosting_account_created', 'hosting_account_suspended', 'hosting_account_unsuspended',
            'hosting_plan_changed', 'hosting_php_changed', 'hosting_account_deleted',
            'domain_vhost_created', 'domain_ssl_issued', 'domain_php_changed',
            'domain_status_toggled', 'domain_vhost_deleted', 'subdomain_vhost_created',
            'subdomain_ssl_issued', 'subdomain_php_changed', 'subdomain_status_toggled',
            'subdomain_vhost_deleted', 'domain_alias_created', 'domain_alias_ssl_issued',
            'domain_alias_status_toggled', 'domain_alias_deleted', 'plan_created',
            'plan_updated', 'plan_deleted', 'plan_status_toggled',
        ];

        $logs = ActivityLog::with('user')
            ->where(function ($q) use ($hostingActions) {
                $q->whereIn('action', $hostingActions)
                    ->orWhere('action', 'like', 'hosting_%')
                    ->orWhere('action', 'like', 'domain_%')
                    ->orWhere('action', 'like', 'subdomain_%')
                    ->orWhere('action', 'like', 'plan_%');
            })
            ->latest('created_at')
            ->limit(1000)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="hosting_audit_log_' . date('Y_m_d_His') . '.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Timestamp', 'Executor', 'Role', 'Action', 'Description', 'IP Address', 'Metadata']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at->toIso8601String(),
                    $log->user ? ($log->user->first_name . ' ' . $log->user->last_name . ' (@' . $log->user->username . ')') : 'System Engine',
                    $log->user ? $log->user->role : 'System',
                    $log->action,
                    $log->description,
                    $log->ip_address,
                    json_encode($log->new_values),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
