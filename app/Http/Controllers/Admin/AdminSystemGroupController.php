<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSystemGroupController extends Controller
{
    /**
     * Display Linux POSIX System Groups.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $groups = $this->getSystemGroups();

        // 4 Clean 3-Tier Metric Stats calculated directly from /etc/group and /etc/passwd
        $privilegedCount = count(array_filter($groups, fn($g) => $g['is_privileged']));
        $daemonCount = count(array_filter($groups, fn($g) => $g['type'] === 'System Daemon Group'));
        $userPrivateCount = count(array_filter($groups, fn($g) => $g['type'] === 'User Private Group'));
        $totalCount = count($groups);

        $stats = [
            'total_groups' => $totalCount,
            'privileged_count' => $privilegedCount,
            'daemon_count' => $daemonCount,
            'user_private_count' => $userPrivateCount,
        ];

        // Filters
        $typeFilter = $request->input('type', 'all'); // 'all', 'privileged', 'daemon', 'user'
        $search = trim($request->input('search', ''));

        $filteredGroups = $groups;

        if ($typeFilter === 'privileged') {
            $filteredGroups = array_filter($filteredGroups, fn($g) => $g['is_privileged']);
        } elseif ($typeFilter === 'daemon') {
            $filteredGroups = array_filter($filteredGroups, fn($g) => $g['type'] === 'System Daemon Group');
        } elseif ($typeFilter === 'user') {
            $filteredGroups = array_filter($filteredGroups, fn($g) => $g['type'] === 'User Private Group');
        }

        if ($search !== '') {
            $filteredGroups = array_filter($filteredGroups, function ($g) use ($search) {
                return str_contains(strtolower($g['name']), strtolower($search))
                    || str_contains((string) $g['gid'], $search)
                    || str_contains(strtolower(implode(' ', $g['members'])), strtolower($search));
            });
        }

        return Inertia::render('Admin/RootTools/SystemGroups/Index', [
            'groups' => array_values($filteredGroups),
            'stats' => $stats,
            'filters' => [
                'type' => $typeFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Inspect detailed group membership.
     */
    public function details(Request $request, string $name): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $name)) {
            return response()->json(['error' => 'Invalid group name format.'], 400);
        }

        $allGroups = $this->getSystemGroups();
        $group = array_values(array_filter($allGroups, fn($g) => $g['name'] === $name))[0] ?? null;

        if (!$group) {
            return response()->json(['error' => "Group '{$name}' not found on system."], 404);
        }

        // Find primary members in /etc/passwd whose gid matches
        $primaryMembers = [];
        if (file_exists('/etc/passwd')) {
            $lines = file('/etc/passwd', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) >= 4 && (int) $parts[3] === $group['gid']) {
                    $primaryMembers[] = $parts[0];
                }
            }
        }

        return response()->json([
            'name' => $group['name'],
            'gid' => $group['gid'],
            'type' => $group['type'],
            'is_privileged' => $group['is_privileged'],
            'supplementary_members' => $group['members'],
            'primary_members' => $primaryMembers,
            'total_associated_users' => count(array_unique(array_merge($primaryMembers, $group['members']))),
        ]);
    }

    /**
     * Parse POSIX groups from /etc/group and /etc/passwd.
     */
    private function getSystemGroups(): array
    {
        if (!file_exists('/etc/group')) {
            return [];
        }

        // Map primary user counts from /etc/passwd
        $primaryGidCounts = [];
        if (file_exists('/etc/passwd')) {
            $passwdLines = file('/etc/passwd', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($passwdLines as $pl) {
                $parts = explode(':', trim($pl));
                if (count($parts) >= 4) {
                    $gid = (int) $parts[3];
                    $primaryGidCounts[$gid] = ($primaryGidCounts[$gid] ?? 0) + 1;
                }
            }
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
                'primary_count' => $primaryGidCounts[$gid] ?? 0,
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
