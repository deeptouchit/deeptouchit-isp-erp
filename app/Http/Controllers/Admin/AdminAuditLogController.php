<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditLogController extends Controller
{
    /**
     * Display comprehensive system activity and change audit trail.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ActivityLog::with('user:id,first_name,last_name,email,role,username');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Category Filter
        if ($category = $request->input('category')) {
            if ($category === 'security') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%ban%')
                        ->orWhere('action', 'like', '%firewall%')
                        ->orWhere('action', 'like', '%security%')
                        ->orWhere('action', 'like', '%2fa%');
                });
            } elseif ($category === 'billing') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%invoice%')
                        ->orWhere('action', 'like', '%payment%')
                        ->orWhere('action', 'like', '%coupon%')
                        ->orWhere('action', 'like', '%credit%')
                        ->orWhere('action', 'like', '%gateway%')
                        ->orWhere('action', 'like', '%transaction%');
                });
            } elseif ($category === 'system') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%maintenance%')
                        ->orWhere('action', 'like', '%flush%')
                        ->orWhere('action', 'like', '%restart%')
                        ->orWhere('action', 'like', '%cron%')
                        ->orWhere('action', 'like', '%queue%')
                        ->orWhere('action', 'like', '%cleanup%');
                });
            } elseif ($category === 'hosting') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%account%')
                        ->orWhere('action', 'like', '%domain%')
                        ->orWhere('action', 'like', '%suspension%')
                        ->orWhere('action', 'like', '%ssl%')
                        ->orWhere('action', 'like', '%backup%');
                });
            } elseif ($category === 'support') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%ticket%')
                        ->orWhere('action', 'like', '%announcement%')
                        ->orWhere('action', 'like', '%canned%')
                        ->orWhere('action', 'like', '%department%');
                });
            }
        }

        $activityLogs = $query->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $all = ActivityLog::all();
        $totalActivities = $all->count();
        $adminActions = $all->where('user_id', '!=', null)->count();
        $securityEvents = $all->filter(fn($l) => str_contains($l->action, 'ban') || str_contains($l->action, 'firewall') || str_contains($l->action, 'security'))->count();
        $uniqueUsers = $all->pluck('user_id')->filter()->unique()->count();

        $stats = [
            'total_activities' => $totalActivities,
            'admin_actions' => $adminActions,
            'security_events' => $securityEvents,
            'unique_users' => $uniqueUsers ?: 1,
        ];

        return Inertia::render('Admin/Logs/Audit/Index', [
            'activityLogs' => $activityLogs,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Export activity audit trail as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $logs = ActivityLog::with('user')->latest('id')->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity_audit_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'User', 'Action', 'Description', 'IP Address', 'Old Values', 'New Values']);

            foreach ($logs as $l) {
                fputcsv($file, [
                    $l->id,
                    $l->created_at?->format('Y-m-d H:i:s'),
                    $l->user ? $l->user->email : 'System / CLI',
                    $l->action,
                    $l->description,
                    $l->ip_address,
                    json_encode($l->old_values),
                    json_encode($l->new_values),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear older activity audit records.
     */
    public function clear(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $days = (int)$request->input('days', 30);
        if ($days > 0) {
            ActivityLog::where('created_at', '<', now()->subDays($days))->delete();
            $msg = "Cleared activity logs older than {$days} days.";
        } else {
            ActivityLog::truncate();
            $msg = "Cleared all activity audit records.";
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'activity_logs_cleared',
            'description' => $msg,
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['days' => $days],
        ]);

        return redirect()->route('admin.logs.audit')
            ->with('success', $msg);
    }
}
