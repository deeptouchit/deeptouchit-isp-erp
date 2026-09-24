<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class AdminProcessController extends Controller
{
    /**
     * Display live Linux process manager and system daemon watcher.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $processes = $this->getRunningProcesses();

        // 4 Clean 3-Tier Metric Stats calculated live from Linux host
        $totalProcesses = count($processes);
        $zombieCount = count(array_filter($processes, fn($p) => str_contains($p['stat'], 'Z')));

        // Load Averages
        $load = sys_getloadavg();
        $loadAvg = sprintf('%.2f, %.2f, %.2f', $load[0] ?? 0.0, $load[1] ?? 0.0, $load[2] ?? 0.0);

        // Memory Usage from /proc/meminfo
        $totalMemMb = 0;
        $availMemMb = 0;
        if (file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $m1)) {
                $totalMemMb = round($m1[1] / 1024);
            }
            if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $m2)) {
                $availMemMb = round($m2[1] / 1024);
            }
        }
        $usedMemMb = max(0, $totalMemMb - $availMemMb);
        $memPercent = $totalMemMb > 0 ? (int) round(($usedMemMb / $totalMemMb) * 100) : 0;

        $topCpuProcess = !empty($processes) ? $processes[0]['command'] : 'None';

        $stats = [
            'total_processes' => $totalProcesses,
            'load_avg' => $loadAvg,
            'top_cpu_process' => $topCpuProcess,
            'used_memory_mb' => $usedMemMb,
            'total_memory_mb' => $totalMemMb,
            'memory_percentage' => $memPercent,
            'zombie_count' => $zombieCount,
        ];

        // Available process owners
        $users = array_values(array_unique(array_column($processes, 'user')));
        sort($users);

        // Filter: Search
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $processes = array_filter($processes, function ($p) use ($search) {
                return str_contains(strtolower($p['command']), strtolower($search))
                    || str_contains(strtolower($p['args']), strtolower($search))
                    || str_contains(strtolower($p['user']), strtolower($search))
                    || str_contains((string) $p['pid'], $search);
            });
        }

        // Filter: User / Owner
        $userFilter = $request->input('user', 'all');
        if ($userFilter !== 'all' && $userFilter !== '') {
            $processes = array_filter($processes, fn($p) => $p['user'] === $userFilter);
        }

        // Filter: Status
        $statusFilter = $request->input('status', 'all');
        if ($statusFilter === 'running') {
            $processes = array_filter($processes, fn($p) => str_contains($p['stat'], 'R'));
        } elseif ($statusFilter === 'sleeping') {
            $processes = array_filter($processes, fn($p) => str_contains($p['stat'], 'S') || str_contains($p['stat'], 'D'));
        } elseif ($statusFilter === 'zombie') {
            $processes = array_filter($processes, fn($p) => str_contains($p['stat'], 'Z'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'cpu');
        $order = strtolower($request->input('order', 'desc'));

        usort($processes, function ($a, $b) use ($sortBy, $order) {
            $valA = $a['cpu'];
            $valB = $b['cpu'];

            if ($sortBy === 'mem') {
                $valA = $a['mem'];
                $valB = $b['mem'];
            } elseif ($sortBy === 'rss') {
                $valA = $a['rss_kb'];
                $valB = $b['rss_kb'];
            } elseif ($sortBy === 'pid') {
                $valA = $a['pid'];
                $valB = $b['pid'];
            }

            if ($valA == $valB) return 0;
            if ($order === 'asc') {
                return $valA < $valB ? -1 : 1;
            }
            return $valA > $valB ? -1 : 1;
        });

        // Pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $totalItems = count($processes);
        $pagedData = array_slice($processes, ($page - 1) * $perPage, $perPage);

        $paginated = new LengthAwarePaginator(
            $pagedData,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Admin/RootTools/Processes/Index', [
            'processes' => $paginated,
            'stats' => $stats,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'user' => $userFilter,
                'status' => $statusFilter,
                'sort_by' => $sortBy,
                'order' => $order,
            ],
        ]);
    }

    /**
     * Terminate or send signal to a running process.
     */
    public function kill(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'pid' => ['required', 'integer', 'min:1'],
            'signal' => ['required', 'in:SIGTERM,SIGKILL,SIGHUP'],
        ]);

        $pid = (int) $validated['pid'];
        $signalName = $validated['signal'];

        // Safety Protections
        $currentPid = function_exists('posix_getpid') ? posix_getpid() : null;
        if ($pid === 1 || $pid === 2 || ($currentPid && $pid === $currentPid)) {
            return redirect()->route('admin.root-tools.processes')
                ->with('error', "Protected process PID {$pid} cannot be terminated.");
        }

        // Map signal name to POSIX signal number
        $sigNum = match ($signalName) {
            'SIGKILL' => 9,
            'SIGHUP' => 1,
            default => 15, // SIGTERM
        };

        $commandName = $this->getProcessNameByPid($pid);

        // Execute signal kill
        $success = false;
        if (function_exists('posix_kill')) {
            $success = @posix_kill($pid, $sigNum);
        } else {
            exec("kill -{$sigNum} {$pid} 2>&1", $out, $code);
            $success = ($code === 0);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'process_signal_sent',
            'description' => "Sent signal {$signalName} to process PID {$pid} ({$commandName}).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['pid' => $pid, 'command' => $commandName],
            'new_values' => ['signal' => $signalName, 'success' => $success],
        ]);

        if ($success) {
            return redirect()->route('admin.root-tools.processes')
                ->with('success', "Signal {$signalName} dispatched to PID {$pid} ({$commandName}).");
        }

        return redirect()->route('admin.root-tools.processes')
            ->with('error', "Unable to send signal to PID {$pid}. It may have already exited or requires root permissions.");
    }

    /**
     * Inspect granular details for a specific PID.
     */
    public function details(Request $request, int $pid): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $procDir = "/proc/{$pid}";
        if (!is_dir($procDir)) {
            return response()->json(['error' => 'Process does not exist or has exited.'], 404);
        }

        $cmdline = '';
        if (file_exists("{$procDir}/cmdline")) {
            $cmdline = str_replace("\0", ' ', file_get_contents("{$procDir}/cmdline"));
        }

        $statusData = [];
        if (file_exists("{$procDir}/status")) {
            $lines = explode("\n", file_get_contents("{$procDir}/status"));
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$k, $v] = explode(':', $line, 2);
                    $statusData[trim($k)] = trim($v);
                }
            }
        }

        $fdCount = 0;
        if (is_dir("{$procDir}/fd")) {
            $fds = @scandir("{$procDir}/fd");
            if ($fds) {
                $fdCount = max(0, count($fds) - 2);
            }
        }

        return response()->json([
            'pid' => $pid,
            'cmdline' => $cmdline ?: ($statusData['Name'] ?? "PID {$pid}"),
            'status' => $statusData,
            'open_file_descriptors' => $fdCount,
        ]);
    }

    /**
     * Parse running processes from `ps`.
     */
    private function getRunningProcesses(): array
    {
        // -eo pid,user,%cpu,%mem,rss,stat,time,args
        $output = shell_exec('ps -eo pid,user,%cpu,%mem,rss,stat,time,args --sort=-%cpu 2>&1');
        if (!$output) {
            return [];
        }

        $lines = explode("\n", trim($output));
        if (count($lines) < 2) {
            return [];
        }

        // Drop header line
        array_shift($lines);

        $list = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line), 8);
            if (count($parts) < 8) continue;

            $pid = (int) $parts[0];
            $user = $parts[1];
            $cpu = (float) $parts[2];
            $mem = (float) $parts[3];
            $rssKb = (int) $parts[4];
            $stat = $parts[5];
            $time = $parts[6];
            $fullArgs = $parts[7];

            $command = basename(explode(' ', $fullArgs)[0]);

            $rssFormatted = $rssKb > 1048576 
                ? round($rssKb / 1048576, 2) . ' GB'
                : ($rssKb > 1024 ? round($rssKb / 1024, 1) . ' MB' : $rssKb . ' KB');

            $list[] = [
                'pid' => $pid,
                'user' => $user,
                'cpu' => $cpu,
                'mem' => $mem,
                'rss_kb' => $rssKb,
                'rss' => $rssFormatted,
                'stat' => $stat,
                'time' => $time,
                'command' => $command,
                'args' => $fullArgs,
                'is_critical' => ($pid <= 2),
            ];
        }

        return $list;
    }

    /**
     * Helper to get command name by PID.
     */
    private function getProcessNameByPid(int $pid): string
    {
        if (file_exists("/proc/{$pid}/comm")) {
            return trim(file_get_contents("/proc/{$pid}/comm"));
        }
        return "PID {$pid}";
    }
}
