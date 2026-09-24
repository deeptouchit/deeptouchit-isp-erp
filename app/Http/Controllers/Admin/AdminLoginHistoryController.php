<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLoginHistoryController extends Controller
{
    /**
     * Display administrator, staff, and client authentication audits.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = LoginHistory::with('user:id,first_name,last_name,email,role,username');

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('browser', 'like', "%{$search}%")
                    ->orWhere('os', 'like', "%{$search}%")
                    ->orWhere('failure_reason', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Role Filter
        if ($role = $request->input('role')) {
            if ($role !== 'all') {
                $query->where('role', $role);
            }
        }

        $loginHistories = $query->latest('login_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats
        $all = LoginHistory::all();
        $totalLogins = $all->count();
        $successCount = $all->where('status', 'success')->count();
        $failedCount = $all->whereIn('status', ['failed', 'blocked'])->count();
        $uniqueIps = $all->pluck('ip_address')->unique()->count();

        $stats = [
            'total_logins' => $totalLogins,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'unique_ips' => $uniqueIps,
        ];

        return Inertia::render('Admin/Logs/LoginHistory/Index', [
            'loginHistories' => $loginHistories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'role']),
        ]);
    }

    /**
     * Export login history audit trail as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $logs = LoginHistory::latest('login_at')->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="login_history_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'Email', 'Role', 'Status', 'IP Address', 'Location', 'Device', 'Browser', 'OS', 'Failure Reason']);

            foreach ($logs as $l) {
                fputcsv($file, [
                    $l->id,
                    $l->login_at?->format('Y-m-d H:i:s'),
                    $l->email,
                    $l->role,
                    $l->status,
                    $l->ip_address,
                    $l->location,
                    $l->device,
                    $l->browser,
                    $l->os,
                    $l->failure_reason,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear older login history records.
     */
    public function clear(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $days = (int)$request->input('days', 30);
        if ($days > 0) {
            LoginHistory::where('login_at', '<', now()->subDays($days))->delete();
            $msg = "Cleared login history older than {$days} days.";
        } else {
            LoginHistory::truncate();
            $msg = "Cleared all login history records.";
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'login_history_cleared',
            'description' => $msg,
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['days' => $days],
        ]);

        return redirect()->route('admin.logs.login-history')
            ->with('success', $msg);
    }
}
