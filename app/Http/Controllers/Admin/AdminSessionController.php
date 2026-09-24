<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminSessionController extends Controller
{
    /**
     * Display authenticated administrator and staff active sessions.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $currentSessionId = $request->session()->getId();

        // Base query for active authenticated sessions
        $query = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select([
                'sessions.id as session_id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.username',
                'users.role',
                'users.admin_role',
                'users.designation',
                'users.is_staff',
            ]);

        // Filter: Show Authenticated by default or all
        $userScope = $request->input('scope', 'authenticated');
        if ($userScope === 'authenticated') {
            $query->whereNotNull('sessions.user_id');
        } elseif ($userScope === 'admins') {
            $query->whereNotNull('sessions.user_id')->where('users.role', 'admin')->where('users.is_staff', false);
        } elseif ($userScope === 'staff') {
            $query->whereNotNull('sessions.user_id')->where('users.is_staff', true);
        }

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('sessions.ip_address', 'like', "%{$search}%")
                    ->orWhere('sessions.user_agent', 'like', "%{$search}%")
                    ->orWhere('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%");
            });
        }

        $sessions = $query->orderBy('sessions.last_activity', 'desc')
            ->paginate(15)
            ->through(function ($session) use ($currentSessionId) {
                $ua = $session->user_agent ?: '';
                $deviceInfo = $this->parseUserAgent($ua);

                return [
                    'id' => $session->session_id,
                    'user_id' => $session->user_id,
                    'user' => $session->user_id ? [
                        'id' => $session->user_id,
                        'name' => trim("{$session->first_name} {$session->last_name}") ?: $session->username,
                        'email' => $session->email,
                        'username' => $session->username,
                        'role' => $session->admin_role ?: ($session->is_staff ? $session->designation : 'Administrator'),
                        'is_staff' => (bool) $session->is_staff,
                    ] : null,
                    'ip_address' => $session->ip_address,
                    'device' => $deviceInfo['device'],
                    'os' => $deviceInfo['os'],
                    'browser' => $deviceInfo['browser'],
                    'last_active_at' => Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                    'last_active_human' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'is_current' => $session->session_id === $currentSessionId,
                ];
            })
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $allSessions = DB::table('sessions')->get();
        $authSessions = $allSessions->whereNotNull('user_id');
        $totalActiveSessions = $authSessions->count();
        $uniqueOperators = $authSessions->pluck('user_id')->unique()->count();
        $staticIpSessions = $authSessions->where('ip_address', '103.59.177.138')->count();
        $guestSessions = $allSessions->whereNull('user_id')->count();

        $stats = [
            'total_active_sessions' => $totalActiveSessions,
            'unique_operators' => $uniqueOperators,
            'static_ip_sessions' => $staticIpSessions,
            'guest_sessions' => $guestSessions,
        ];

        return Inertia::render('Admin/Administration/Sessions/Index', [
            'sessions' => $sessions,
            'stats' => $stats,
            'filters' => $request->only(['search', 'scope']),
        ]);
    }

    /**
     * Terminate / Kill a specific active session.
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        $this->authorize('create', User::class);

        $session = DB::table('sessions')->where('id', $sessionId)->first();

        if ($session) {
            DB::table('sessions')->where('id', $sessionId)->delete();

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'session_terminated',
                'description' => "Terminated active session '{$sessionId}' from IP {$session->ip_address}.",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => ['session_id' => $sessionId, 'user_id' => $session->user_id],
                'new_values' => [],
            ]);
        }

        return redirect()->route('admin.administration.sessions')
            ->with('success', 'Session terminated successfully.');
    }

    /**
     * Terminate all other sessions for the currently logged-in administrator.
     */
    public function terminateAllOther(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $currentSessionId = $request->session()->getId();
        $userId = auth()->id();

        $deletedCount = DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        ActivityLog::create([
            'user_id' => $userId ?: 1,
            'action' => 'all_other_sessions_terminated',
            'description' => "Terminated {$deletedCount} other active sessions for current user.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['terminated_count' => $deletedCount],
        ]);

        return redirect()->route('admin.administration.sessions')
            ->with('success', "Revoked {$deletedCount} other active session(s).");
    }

    /**
     * Helper to parse user agent into device, OS, and browser.
     */
    private function parseUserAgent(string $ua): array
    {
        $os = 'Linux';
        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) $os = 'macOS';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
        elseif (str_contains($ua, 'Android')) $os = 'Android';

        $browser = 'Chrome';
        if (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
        elseif (str_contains($ua, 'Edg')) $browser = 'Edge';
        elseif (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) $browser = 'Opera';

        $device = 'Desktop';
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone')) {
            $device = 'Mobile';
        } elseif (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            $device = 'Tablet';
        }

        return [
            'os' => $os,
            'browser' => $browser,
            'device' => $device,
        ];
    }
}
