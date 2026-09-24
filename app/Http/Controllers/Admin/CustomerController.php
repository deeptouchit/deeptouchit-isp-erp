<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display directory of active hosting clients & subscribers.
     */
    public function active(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->where('role', 'client')
            ->where('status', 'active')
            ->with(['reseller:id,first_name,last_name,username,email,company'])
            ->withCount(['subscriptions', 'invoices', 'tickets']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Reseller Filter
        if ($resellerId = $request->input('reseller_id')) {
            $query->where('created_by', $resellerId);
        }

        $customers = $query->latest('created_at')->paginate(15)->withQueryString();

        // Calculate active client telemetry stats
        $activeClientsCount = User::where('role', 'client')->where('status', 'active')->count();
        $totalSubscriptionsCount = Subscription::where('status', 'active')->count();
        $resellersCount = User::where('role', 'reseller')->where('status', 'active')->count();
        $allClientsCount = User::where('role', 'client')->count();

        $stats = [
            'active_clients' => $activeClientsCount,
            'total_clients' => $allClientsCount,
            'active_subscriptions' => $totalSubscriptionsCount,
            'active_resellers' => $resellersCount,
            'operational_rate' => $allClientsCount > 0 ? round(($activeClientsCount / $allClientsCount) * 100, 1) : 100,
        ];

        $resellers = User::where('role', 'reseller')
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'company')
            ->orderBy('first_name', 'asc')
            ->get();

        return Inertia::render('Admin/Customers/Active', [
            'customers' => $customers,
            'stats' => $stats,
            'resellers' => $resellers,
            'filters' => $request->only(['search', 'reseller_id']),
        ]);
    }

    /**
     * Display all customer subscribers regardless of status.
     */
    public function all(Request $request): Response
    {
        return app(UserController::class)->index($request);
    }

    /**
     * Display directory of suspended / quarantined customer accounts.
     */
    public function suspended(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->where('role', 'client')
            ->where('status', 'suspended')
            ->with(['reseller:id,first_name,last_name,username,email,company'])
            ->withCount(['subscriptions', 'invoices', 'tickets']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Reseller Filter
        if ($resellerId = $request->input('reseller_id')) {
            $query->where('created_by', $resellerId);
        }

        $customers = $query->latest('updated_at')->paginate(15)->withQueryString();

        $suspendedClientsCount = User::where('role', 'client')->where('status', 'suspended')->count();
        $allClientsCount = User::where('role', 'client')->count();
        $frozenSubsCount = Subscription::whereHas('user', fn ($q) => $q->where('status', 'suspended'))->count();
        $activeClientsCount = User::where('role', 'client')->where('status', 'active')->count();

        $stats = [
            'suspended_clients' => $suspendedClientsCount,
            'total_clients' => $allClientsCount,
            'frozen_subscriptions' => $frozenSubsCount,
            'active_clients' => $activeClientsCount,
            'quarantine_rate' => $allClientsCount > 0 ? round(($suspendedClientsCount / $allClientsCount) * 100, 1) : 0,
        ];

        $resellers = User::where('role', 'reseller')
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'company')
            ->orderBy('first_name', 'asc')
            ->get();

        return Inertia::render('Admin/Customers/Suspended', [
            'customers' => $customers,
            'stats' => $stats,
            'resellers' => $resellers,
            'filters' => $request->only(['search', 'reseller_id']),
        ]);
    }

    /**
     * Display customer security, telemetry & audit history.
     */
    public function activity(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ActivityLog::query()
            ->with(['user:id,first_name,last_name,username,email,role,status']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Action Filter
        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        // User Filter
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $activityLogs = $query->latest('created_at')->paginate(20)->withQueryString();

        // Calculate Telemetry Stats
        $totalLogsCount = ActivityLog::count();
        $todayLogsCount = ActivityLog::whereDate('created_at', now()->toDateString())->count();
        $uniqueUsersCount = ActivityLog::distinct('user_id')->count('user_id');
        $recentLoginsCount = User::whereNotNull('last_login_at')->count();

        $stats = [
            'total_events' => $totalLogsCount,
            'events_today' => $todayLogsCount,
            'unique_users_active' => $uniqueUsersCount ?: User::where('status', 'active')->count(),
            'recent_logins_count' => $recentLoginsCount,
            'system_health' => '100% Protected',
        ];

        $recentLogins = User::whereNotNull('last_login_at')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'role', 'status', 'last_login_at', 'last_login_ip')
            ->latest('last_login_at')
            ->limit(20)
            ->get();

        $distinctActions = ActivityLog::select('action')->distinct()->pluck('action');

        $usersList = User::select('id', 'first_name', 'last_name', 'username', 'email')
            ->orderBy('first_name', 'asc')
            ->get();

        return Inertia::render('Admin/Customers/Activity', [
            'activityLogs' => $activityLogs,
            'recentLogins' => $recentLogins,
            'stats' => $stats,
            'distinctActions' => $distinctActions,
            'usersList' => $usersList,
            'filters' => $request->only(['search', 'action', 'user_id']),
        ]);
    }
}
