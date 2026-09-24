<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemUserController extends Controller
{
    /**
     * Display Linux System Users and POSIX Groups.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = $this->getSystemUsers();
        $groups = $this->getSystemGroups();

        // 4 Clean 3-Tier Metric Stats calculated directly from Linux system files
        $interactiveUsers = array_filter($users, fn($u) => $u['is_interactive'] || $u['uid'] === 0);
        $serviceUsers = array_filter($users, fn($u) => !$u['is_interactive'] && $u['uid'] !== 0);
        
        $sudoGroup = array_values(array_filter($groups, fn($g) => $g['name'] === 'sudo' || $g['name'] === 'wheel'))[0] ?? null;
        $sudoMembers = $sudoGroup['members'] ?? ['root'];
        if (!in_array('root', $sudoMembers)) {
            array_unshift($sudoMembers, 'root');
        }

        $stats = [
            'interactive_count' => count($interactiveUsers),
            'system_daemon_count' => count($serviceUsers),
            'sudo_operators_count' => count($sudoMembers),
            'total_groups_count' => count($groups),
            'total_users_count' => count($users),
        ];

        // Filters for Users
        $typeFilter = $request->input('type', 'all'); // 'all', 'interactive', 'system', 'sudo'
        $search = trim($request->input('search', ''));

        $filteredUsers = $users;

        if ($typeFilter === 'interactive') {
            $filteredUsers = array_filter($filteredUsers, fn($u) => $u['is_interactive'] || $u['uid'] === 0);
        } elseif ($typeFilter === 'system') {
            $filteredUsers = array_filter($filteredUsers, fn($u) => !$u['is_interactive'] && $u['uid'] !== 0);
        } elseif ($typeFilter === 'sudo') {
            $filteredUsers = array_filter($filteredUsers, fn($u) => in_array($u['username'], $sudoMembers));
        }

        if ($search !== '') {
            $filteredUsers = array_filter($filteredUsers, function ($u) use ($search) {
                return str_contains(strtolower($u['username']), strtolower($search))
                    || str_contains((string) $u['uid'], $search)
                    || str_contains(strtolower($u['home']), strtolower($search))
                    || str_contains(strtolower($u['shell']), strtolower($search));
            });
        }

        // Active tab view
        $activeTab = $request->input('tab', 'users'); // 'users' | 'groups'

        return Inertia::render('Admin/RootTools/SystemUsers/Index', [
            'users' => array_values($filteredUsers),
            'groups' => $groups,
            'stats' => $stats,
            'filters' => [
                'type' => $typeFilter,
                'search' => $search,
                'tab' => $activeTab,
            ],
        ]);
    }

    /**
     * Inspect detailed system user info.
     */
    public function details(Request $request, string $username): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            return response()->json(['error' => 'Invalid username format.'], 400);
        }

        $allUsers = $this->getSystemUsers();
        $user = array_values(array_filter($allUsers, fn($u) => $u['username'] === $username))[0] ?? null;

        if (!$user) {
            return response()->json(['error' => "User '{$username}' not found on system."], 404);
        }

        // Check if home dir exists and size
        $homeExists = is_dir($user['home']);
        $homeSize = 'N/A';
        if ($homeExists && is_readable($user['home'])) {
            $safeHome = escapeshellarg($user['home']);
            $duOutput = shell_exec("du -sh {$safeHome} 2>/dev/null");
            if ($duOutput && preg_match('/^([^\s]+)/', trim($duOutput), $m)) {
                $homeSize = $m[1];
            }
        }

        // Get user supplementary groups via id -Gn
        $groupsStr = shell_exec("id -Gn " . escapeshellarg($username) . " 2>/dev/null");
        $groupsList = $groupsStr ? array_filter(explode(' ', trim($groupsStr))) : [$user['group']];

        return response()->json([
            'username' => $user['username'],
            'uid' => $user['uid'],
            'gid' => $user['gid'],
            'primary_group' => $user['group'],
            'groups' => array_values($groupsList),
            'home' => $user['home'],
            'home_exists' => $homeExists,
            'home_size' => $homeSize,
            'shell' => $user['shell'],
            'is_interactive' => $user['is_interactive'],
            'is_root' => $user['uid'] === 0,
        ]);
    }

    /**
     * Toggle interactive shell for a user (/bin/bash vs /usr/sbin/nologin).
     */
    public function toggleShell(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'shell' => ['required', 'string', 'in:/bin/bash,/bin/sh,/bin/false,/usr/sbin/nologin'],
        ]);

        $username = $validated['username'];
        $newShell = $validated['shell'];

        // Root cannot be locked out
        if ($username === 'root' && ($newShell === '/bin/false' || $newShell === '/usr/sbin/nologin')) {
            return redirect()->route('admin.root-tools.system-users')
                ->with('error', 'Cannot disable login shell for root superuser.');
        }

        if (app()->environment('testing')) {
            $returnCode = 0;
            $output = ["usermod: updated shell for {$username} to {$newShell}"];
        } else {
            $safeUser = escapeshellarg($username);
            $safeShell = escapeshellarg($newShell);
            exec("usermod -s {$safeShell} {$safeUser} 2>&1", $output, $returnCode);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'toggle_system_user_shell',
            'description' => "Changed system shell for {$username} to {$newShell}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['username' => $username],
            'new_values' => ['shell' => $newShell, 'return_code' => $returnCode],
        ]);

        if ($returnCode === 0) {
            return redirect()->route('admin.root-tools.system-users')
                ->with('success', "Shell for '{$username}' updated to '{$newShell}'.");
        }

        $errorMsg = !empty($output) ? implode(' ', array_slice($output, -3)) : 'Permission denied or usermod failed.';
        return redirect()->route('admin.root-tools.system-users')
            ->with('error', "Failed to update shell: {$errorMsg}");
    }

    /**
     * Parse system users from /etc/passwd and /etc/group.
     */
    private function getSystemUsers(): array
    {
        if (!file_exists('/etc/passwd')) {
            return [];
        }

        // Build GID -> Group Name map
        $groupMap = [];
        if (file_exists('/etc/group')) {
            $grpLines = file('/etc/group', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($grpLines as $gl) {
                $gparts = explode(':', trim($gl));
                if (count($gparts) >= 3) {
                    $groupMap[(int) $gparts[2]] = $gparts[0];
                }
            }
        }

        $lines = file('/etc/passwd', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $users = [];

        foreach ($lines as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) < 7) continue;

            $username = $parts[0];
            $uid = (int) $parts[2];
            $gid = (int) $parts[3];
            $gecos = $parts[4];
            $home = $parts[5];
            $shell = $parts[6];

            $isInteractive = ($uid >= 1000 && $uid < 65534) 
                && !str_contains($shell, 'nologin') 
                && !str_contains($shell, 'false');

            $type = 'System Service Daemon';
            if ($uid === 0) {
                $type = 'Root Superuser';
            } elseif ($uid >= 1000 && $uid < 65534) {
                $type = $isInteractive ? 'Interactive Human' : 'Isolated Service User';
            } elseif ($uid === 65534) {
                $type = 'Unprivileged Guest (nobody)';
            }

            $users[] = [
                'username' => $username,
                'uid' => $uid,
                'gid' => $gid,
                'group' => $groupMap[$gid] ?? "gid-{$gid}",
                'gecos' => $gecos ?: $username,
                'home' => $home,
                'shell' => $shell,
                'type' => $type,
                'is_interactive' => $isInteractive,
                'has_shell' => !str_contains($shell, 'nologin') && !str_contains($shell, 'false'),
            ];
        }

        // Sort: Root and Interactive users first, then by UID
        usort($users, function ($a, $b) {
            if ($a['uid'] === 0) return -1;
            if ($b['uid'] === 0) return 1;
            if ($a['is_interactive'] !== $b['is_interactive']) {
                return $a['is_interactive'] ? -1 : 1;
            }
            return $a['uid'] <=> $b['uid'];
        });

        return $users;
    }

    /**
     * Parse POSIX groups from /etc/group.
     */
    private function getSystemGroups(): array
    {
        if (!file_exists('/etc/group')) {
            return [];
        }

        $lines = file('/etc/group', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $groups = [];

        foreach ($lines as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) < 3) continue;

            $name = $parts[0];
            $gid = (int) $parts[2];
            $membersStr = $parts[3] ?? '';
            $members = array_filter(array_map('trim', explode(',', $membersStr)));

            $type = 'Standard POSIX Group';
            if ($gid === 0 || $name === 'root') {
                $type = 'Root Authority';
            } elseif ($name === 'sudo' || $name === 'wheel' || $name === 'admin') {
                $type = 'Privileged Sudoers';
            } elseif ($gid >= 1000 && $gid < 65534) {
                $type = 'User Private Group';
            } elseif ($gid < 1000) {
                $type = 'System Daemon Group';
            }

            $groups[] = [
                'name' => $name,
                'gid' => $gid,
                'members' => array_values($members),
                'member_count' => count($members),
                'type' => $type,
                'is_privileged' => ($name === 'sudo' || $name === 'wheel' || $name === 'root'),
            ];
        }

        // Sort: Privileged groups first, then by GID
        usort($groups, function ($a, $b) {
            if ($a['is_privileged'] !== $b['is_privileged']) {
                return $a['is_privileged'] ? -1 : 1;
            }
            return $a['gid'] <=> $b['gid'];
        });

        return $groups;
    }
}
