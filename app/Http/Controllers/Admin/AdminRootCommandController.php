<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRootCommandController extends Controller
{
    /**
     * Display Linux Root Command Execution Runner & Presets.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        // System Architecture & Load
        $kernel = php_uname('s') . ' ' . php_uname('r');
        $arch = php_uname('m');
        $uptimeRaw = shell_exec('uptime -p 2>/dev/null') ?: 'up unknown';
        $uptime = trim(str_replace('up ', '', $uptimeRaw));
        $load = sys_getloadavg();

        $presets = $this->getPresets();
        $totalExecutions = ActivityLog::where('action', 'run_root_command')->count();

        // 4 Clean 3-Tier Metric Stats calculated live from Linux host
        $stats = [
            'kernel' => $kernel,
            'arch' => $arch,
            'uptime' => $uptime,
            'load_1m' => number_format($load[0] ?? 0.0, 2),
            'load_5m' => number_format($load[1] ?? 0.0, 2),
            'presets_count' => count($presets),
            'total_executions' => $totalExecutions,
        ];

        // Recent Executions History
        $history = ActivityLog::where('action', 'run_root_command')
            ->with('user:id,name,email')
            ->latest()
            ->take(15)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_name' => $log->user->name ?? 'Administrator',
                    'command' => $log->new_values['command'] ?? 'Unknown command',
                    'cwd' => $log->new_values['cwd'] ?? '/var/www/deeptouchhost',
                    'exit_code' => $log->new_values['exit_code'] ?? 0,
                    'duration_ms' => $log->new_values['duration_ms'] ?? 0,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Admin/RootTools/Commands/Index', [
            'stats' => $stats,
            'presets' => $presets,
            'history' => $history,
        ]);
    }

    /**
     * Execute a shell command synchronously and stream output back.
     */
    public function execute(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'command' => ['required', 'string', 'max:1000'],
            'cwd' => ['nullable', 'string', 'max:255'],
        ]);

        $command = trim($validated['command']);
        $cwd = trim($validated['cwd'] ?? '/var/www/deeptouchhost');

        // Verify valid cwd
        if (!is_dir($cwd)) {
            $cwd = '/var/www/deeptouchhost';
        }

        // Safety blacklist for dangerous commands
        $blockedPatterns = [
            '/\brm\s+-[rfR]*\s+[\/\*]/',
            '/\bmkfs\b/',
            '/\bdd\s+if=\/dev\/zero\s+of=\/dev\/sd/',
            '/\b(shutdown|poweroff|init\s+0)\b/',
            '/\b(reboot|init\s+6)\b/',
            '/:(){ :|:& };:/', // forkbomb
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $command)) {
                return response()->json([
                    'success' => false,
                    'command' => $command,
                    'cwd' => $cwd,
                    'exit_code' => 126,
                    'duration_ms' => 0,
                    'output' => "Execution blocked by DeepTouchHost Safety Guard: Command matches restricted high-risk destructive signature.",
                ], 403);
            }
        }

        // Testing environment mock
        if (app()->environment('testing')) {
            $startTime = microtime(true);
            $durationMs = round((microtime(true) - $startTime) * 1000, 1);

            ActivityLog::create([
                'user_id' => auth()->id() ?: 1,
                'action' => 'run_root_command',
                'description' => "Executed command: {$command}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'new_values' => [
                    'command' => $command,
                    'cwd' => $cwd,
                    'exit_code' => 0,
                    'duration_ms' => $durationMs,
                ],
            ]);

            return response()->json([
                'success' => true,
                'command' => $command,
                'cwd' => $cwd,
                'exit_code' => 0,
                'duration_ms' => $durationMs,
                'output' => "Mock test output for: {$command}",
            ]);
        }

        $startTime = microtime(true);

        // Execute in bash with a 15-second timeout
        $escapedCwd = escapeshellarg($cwd);
        $escapedCmd = escapeshellarg($command);
        $fullCmd = "cd {$escapedCwd} && timeout 15s bash -c {$escapedCmd} 2>&1";

        exec($fullCmd, $outputLines, $returnCode);

        $endTime = microtime(true);
        $durationMs = round(($endTime - $startTime) * 1000, 1);
        $rawOutput = implode("\n", $outputLines);

        if ($returnCode === 124) {
            $rawOutput .= "\n[DeepTouchHost Watchdog]: Execution terminated because command exceeded maximum timeout limit of 15 seconds.";
        }

        // Log execution to ActivityLog
        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'run_root_command',
            'description' => "Executed command: {$command}",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Web Terminal',
            'new_values' => [
                'command' => $command,
                'cwd' => $cwd,
                'exit_code' => $returnCode,
                'duration_ms' => $durationMs,
            ],
        ]);

        return response()->json([
            'success' => $returnCode === 0,
            'command' => $command,
            'cwd' => $cwd,
            'exit_code' => $returnCode,
            'duration_ms' => $durationMs,
            'output' => $rawOutput ?: "(Command finished with exit code {$returnCode} and no output)",
        ]);
    }

    /**
     * Catalog of curated diagnostic and maintenance presets.
     */
    private function getPresets(): array
    {
        return [
            [
                'category' => 'Storage & Disks',
                'name' => 'Disk Space & Mounts',
                'command' => 'df -hT --exclude-type=tmpfs --exclude-type=devtmpfs',
                'description' => 'Filesystem type, total size, disk usage percentage and mount points.',
            ],
            [
                'category' => 'Storage & Disks',
                'name' => 'Block Storage Devices',
                'command' => 'lsblk -o NAME,SIZE,FSTYPE,TYPE,MOUNTPOINT',
                'description' => 'Storage partitions, device paths, and attached block volume trees.',
            ],
            [
                'category' => 'Storage & Disks',
                'name' => 'Top 10 Largest Log Files',
                'command' => 'du -sh /var/log/* 2>/dev/null | sort -hr | head -n 10',
                'description' => 'Locate heavy logs taking up disk capacity in /var/log.',
            ],
            [
                'category' => 'Memory & Kernel',
                'name' => 'Physical & Swap Memory',
                'command' => 'free -h -w',
                'description' => 'Wide memory report showing buffers, cache, available RAM and swap.',
            ],
            [
                'category' => 'Memory & Kernel',
                'name' => 'Kernel Virtual Memory Stats',
                'command' => 'vmstat 1 3',
                'description' => 'Sample kernel memory, swap paging, I/O blocks, and CPU tick distribution.',
            ],
            [
                'category' => 'Network & Sockets',
                'name' => 'Kernel Socket Summary',
                'command' => 'ss -s',
                'description' => 'Total active TCP, UDP, RAW and UNIX domain sockets in Linux kernel.',
            ],
            [
                'category' => 'Network & Sockets',
                'name' => 'DNS Stub Resolver Status',
                'command' => 'resolvectl status 2>/dev/null || systemd-resolve --status',
                'description' => 'Uplink DNS nameservers, search domains, and DNSSEC validation state.',
            ],
            [
                'category' => 'Security & SSH',
                'name' => 'Last 10 User Logins',
                'command' => 'last -n 10',
                'description' => 'Recent user authentication sessions, tty, remote IP and durations.',
            ],
            [
                'category' => 'Security & SSH',
                'name' => 'Recent SSH Auth Logs',
                'command' => 'journalctl -u ssh -n 20 --no-pager 2>/dev/null || grep sshd /var/log/auth.log | tail -n 20',
                'description' => 'Audit incoming SSH connection handshakes and authentication logs.',
            ],
            [
                'category' => 'Hosting Daemons',
                'name' => 'Nginx Syntax Validation',
                'command' => 'nginx -t',
                'description' => 'Validate web server configuration syntax and test all virtual hosts.',
            ],
            [
                'category' => 'Hosting Daemons',
                'name' => 'Active PHP CLI & Engines',
                'command' => 'php -v && php -m | grep -E "(opcache|redis|pdo|curl|mbstring|gd|imagick)"',
                'description' => 'Installed PHP CLI engine version and core hosting acceleration extensions.',
            ],
            [
                'category' => 'Hosting Daemons',
                'name' => 'MySQL / MariaDB Status',
                'command' => 'mysqladmin -u root status 2>/dev/null || systemctl status mysql --no-pager -n 5',
                'description' => 'Database server uptime, active threads, queries per second and slow queries.',
            ],
        ];
    }
}
