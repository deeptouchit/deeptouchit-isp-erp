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
use Symfony\Component\Process\Process;

class TerminalController extends Controller
{
    /**
     * Display the Root Web SSH Terminal interface.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $defaultDir = is_dir('/root') ? '/root' : (is_dir('/var/www') ? '/var/www' : '/');
        $cwd = session('terminal_cwd', $defaultDir);
        if (!is_dir($cwd)) {
            $cwd = $defaultDir;
            session(['terminal_cwd' => $cwd]);
        }

        // Gather server environment metadata
        $hostname = gethostname() ?: 'deeptouchhost-node';
        $kernel = php_uname('r') ?: '6.8.0-generic';
        $distro = 'Ubuntu 24.04 LTS (Noble Numbat)';
        if (file_exists('/etc/os-release')) {
            $osRelease = parse_ini_file('/etc/os-release');
            if (!empty($osRelease['PRETTY_NAME'])) {
                $distro = $osRelease['PRETTY_NAME'];
            }
        }

        $user = get_current_user() ?: 'root';
        $shell = getenv('SHELL') ?: '/bin/bash';

        // Calculate Uptime
        $uptime = 'Active';
        if (file_exists('/proc/uptime')) {
            $uptimeSeconds = (int) floatval(explode(' ', file_get_contents('/proc/uptime'))[0]);
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            $uptime = "{$days}d {$hours}h {$minutes}m";
        }

        // Load Averages
        $load = sys_getloadavg();
        $loadStr = sprintf('%.2f, %.2f, %.2f', $load[0] ?? 0.12, $load[1] ?? 0.08, $load[2] ?? 0.05);

        // Memory info
        $totalMem = '8.0 GB';
        $freeMem = '4.2 GB';
        if (file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatches);
            if (!empty($totalMatches[1])) {
                $totalMem = round($totalMatches[1] / 1024 / 1024, 1) . ' GB';
            }
            if (!empty($availMatches[1])) {
                $freeMem = round($availMatches[1] / 1024 / 1024, 1) . ' GB';
            }
        }

        $stats = [
            'hostname' => $hostname,
            'kernel' => $kernel,
            'distro' => $distro,
            'current_user' => $user,
            'shell' => $shell,
            'cwd' => $cwd,
            'uptime' => $uptime,
            'load_avg' => $loadStr,
            'total_ram' => $totalMem,
            'free_ram' => $freeMem,
            'server_ip' => \App\Support\ServerHelper::getPublicIp(),
            'ssh_port' => 22,
        ];

        // Audit Logs (recent 25 terminal commands)
        $auditLogs = ActivityLog::where('action', 'terminal_command')
            ->with('user:id,first_name,last_name,username,email')
            ->latest()
            ->limit(25)
            ->get();

        // Categorized Quick Command Presets
        $presets = [
            'System & Diagnostics' => [
                ['name' => 'Kernel & Architecture', 'cmd' => 'uname -a', 'desc' => 'Print all system architecture details'],
                ['name' => 'System Uptime', 'cmd' => 'uptime', 'desc' => 'Show how long the system has been running'],
                ['name' => 'Current User & Groups', 'cmd' => 'id && whoami', 'desc' => 'Display user ID and active group privileges'],
                ['name' => 'OS Release Specs', 'cmd' => 'cat /etc/os-release', 'desc' => 'Show Linux distribution specifications'],
            ],
            'Resources & Hardware' => [
                ['name' => 'Memory Statistics', 'cmd' => 'free -h', 'desc' => 'Display total, used, and free physical memory'],
                ['name' => 'Disk Space Usage', 'cmd' => 'df -h -x tmpfs -x devtmpfs', 'desc' => 'Report file system disk space utilization'],
                ['name' => 'Top CPU Consumers', 'cmd' => 'ps aux --sort=-%cpu | head -n 10', 'desc' => 'List top 10 processes consuming CPU'],
                ['name' => 'Top Memory Consumers', 'cmd' => 'ps aux --sort=-%mem | head -n 10', 'desc' => 'List top 10 processes consuming RAM'],
            ],
            'Web Stack & Daemons' => [
                ['name' => 'Nginx Status', 'cmd' => 'systemctl status nginx --no-pager', 'desc' => 'Check Nginx reverse proxy service state'],
                ['name' => 'MariaDB Status', 'cmd' => 'systemctl status mariadb --no-pager', 'desc' => 'Check MySQL / MariaDB database service state'],
                ['name' => 'PHP-FPM Status', 'cmd' => 'systemctl status php8.2-fpm --no-pager', 'desc' => 'Check default PHP-FPM pool status'],
                ['name' => 'FTP Daemon Status', 'cmd' => 'systemctl status vsftpd --no-pager', 'desc' => 'Check vsftpd daemon status'],
                ['name' => 'SSH Server Status', 'cmd' => 'systemctl status sshd --no-pager', 'desc' => 'Check OpenSSH daemon status'],
            ],
            'Network & Sockets' => [
                ['name' => 'Listening TCP/UDP Ports', 'cmd' => 'ss -tulpn', 'desc' => 'List all open ports and listening socket processes'],
                ['name' => 'IP Network Interfaces', 'cmd' => 'ip -br addr', 'desc' => 'Show brief network interface and IP configuration'],
                ['name' => 'Active Firewall Status', 'cmd' => 'ufw status verbose', 'desc' => 'Check UFW firewall rules and policies'],
            ],
            'PHP & Runtime' => [
                ['name' => 'CLI PHP Version', 'cmd' => 'php -v', 'desc' => 'Show PHP version and Zend Engine information'],
                ['name' => 'Compiled PHP Modules', 'cmd' => 'php -m', 'desc' => 'List all loaded compiled PHP extensions'],
                ['name' => 'Composer Version', 'cmd' => 'composer --version', 'desc' => 'Check Composer dependency manager version'],
                ['name' => 'Node & NPM Runtime', 'cmd' => 'node -v && npm -v', 'desc' => 'Display active Node.js and NPM versions'],
            ],
        ];

        return Inertia::render('Admin/RootTools/Terminal/Index', [
            'stats' => $stats,
            'auditLogs' => $auditLogs,
            'presets' => $presets,
        ]);
    }

    /**
     * Execute an interactive command via the web console.
     */
    public function execute(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'command' => 'required|string|max:2000',
        ]);

        $rawCommand = trim($validated['command']);
        $defaultDir = is_dir('/root') ? '/root' : (is_dir('/var/www') ? '/var/www' : '/');
        $cwd = session('terminal_cwd', $defaultDir);
        if (!is_dir($cwd)) {
            $cwd = $defaultDir;
        }

        $startTime = microtime(true);

        // Handle 'clear' command
        if ($rawCommand === 'clear' || $rawCommand === 'cls') {
            return response()->json([
                'command' => $rawCommand,
                'output' => '',
                'exit_code' => 0,
                'cwd' => $cwd,
                'is_clear' => true,
                'execution_time_ms' => 1,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        // Handle 'cd' directory navigation
        if (preg_match('/^cd(?:\s+(.+))?$/', $rawCommand, $matches)) {
            $targetDir = isset($matches[1]) ? trim($matches[1]) : '~';
            
            if ($targetDir === '~' || $targetDir === '') {
                $newDir = is_dir('/root') ? '/root' : (getenv('HOME') ?: '/');
            } elseif ($targetDir === '-') {
                $newDir = session('terminal_prev_cwd', $cwd);
            } elseif (str_starts_with($targetDir, '/')) {
                $newDir = $targetDir;
            } else {
                $newDir = rtrim($cwd, '/') . '/' . $targetDir;
            }

            $realPath = realpath($newDir);

            // Smart Hosting Directory Fallback (if relative path not found in current cwd)
            if (!$realPath || !is_dir($realPath)) {
                $knownShortcuts = [
                    'vhosts' => '/var/www/vhosts',
                    'deeptouchhost' => '/var/www/deeptouchhost',
                    'nginx' => '/etc/nginx',
                    'logs' => '/var/log',
                    'log' => '/var/log',
                    'www' => '/var/www',
                ];

                $cleanTarget = strtolower(trim($targetDir, " /'\""));
                if (isset($knownShortcuts[$cleanTarget]) && is_dir($knownShortcuts[$cleanTarget])) {
                    $realPath = $knownShortcuts[$cleanTarget];
                } elseif (is_dir("/var/www/{$targetDir}")) {
                    $realPath = realpath("/var/www/{$targetDir}");
                }
            }

            if ($realPath && is_dir($realPath)) {
                session(['terminal_prev_cwd' => $cwd]);
                session(['terminal_cwd' => $realPath]);

                $executionTime = round((microtime(true) - $startTime) * 1000, 2);

                $this->logCommand($request->user(), $rawCommand, 0, $executionTime);

                return response()->json([
                    'command' => $rawCommand,
                    'output' => '',
                    'exit_code' => 0,
                    'cwd' => $realPath,
                    'execution_time_ms' => $executionTime,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            } else {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $errorMsg = "bash: cd: {$targetDir}: No such file or directory\n";

                $this->logCommand($request->user(), $rawCommand, 1, $executionTime);

                return response()->json([
                    'command' => $rawCommand,
                    'output' => $errorMsg,
                    'exit_code' => 1,
                    'cwd' => $cwd,
                    'execution_time_ms' => $executionTime,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }
        }

        // Dangerous destructive command protection
        $dangerousPatterns = [
            '/\brm\s+-[rfRF]{1,4}\s+\/(?:\s|$)/',
            '/\bmkfs\b/',
            '/\bdd\s+if=.*of=\/dev\/(?:sd|hd|nvme|vd)/',
            '/>\s*\/dev\/sda/',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $rawCommand)) {
                $executionTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->logCommand($request->user(), $rawCommand, 126, $executionTime);

                return response()->json([
                    'command' => $rawCommand,
                    'output' => "HostingOS Security: Command blocked by Root Guard protection system.\n",
                    'exit_code' => 126,
                    'cwd' => $cwd,
                    'execution_time_ms' => $executionTime,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }
        }

        // Execute command via Process
        try {
            $execDir = (is_dir($cwd) && is_readable($cwd)) ? $cwd : (is_dir('/var/www') ? '/var/www' : base_path());
            $wrappedCommand = "cd " . escapeshellarg($cwd) . " 2>/dev/null; " . $rawCommand;

            $process = Process::fromShellCommandline($wrappedCommand, $execDir);
            $process->setTimeout(20);
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            $fullOutput = $output;
            if (!empty($errorOutput)) {
                $fullOutput .= (!empty($fullOutput) ? "\n" : '') . $errorOutput;
            }

            if (empty($fullOutput) && $exitCode === 0) {
                $fullOutput = '';
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logCommand($request->user(), $rawCommand, $exitCode, $executionTime);

            return response()->json([
                'command' => $rawCommand,
                'output' => $fullOutput,
                'exit_code' => $exitCode,
                'cwd' => $cwd,
                'execution_time_ms' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logCommand($request->user(), $rawCommand, 1, $executionTime);

            return response()->json([
                'command' => $rawCommand,
                'output' => "Execution Error: " . $e->getMessage() . "\n",
                'exit_code' => 1,
                'cwd' => $cwd,
                'execution_time_ms' => $executionTime,
                'timestamp' => now()->toDateTimeString(),
            ]);
        }
    }

    /**
     * Clear terminal audit logs.
     */
    public function clearLogs(): RedirectResponse
    {
        $this->authorize('create', User::class);

        ActivityLog::where('action', 'terminal_command')->delete();

        return redirect()->back()
            ->with('success', 'Terminal execution audit logs cleared successfully.');
    }

    /**
     * Helper to log command execution for security audit trail.
     */
    protected function logCommand(?User $user, string $command, int $exitCode, float $durationMs): void
    {
        try {
            ActivityLog::create([
                'user_id' => $user?->id,
                'action' => 'terminal_command',
                'description' => "Executed: `{$command}` (Exit {$exitCode}, {$durationMs}ms)",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'new_values' => [
                    'command' => $command,
                    'exit_code' => $exitCode,
                    'duration_ms' => $durationMs,
                ],
            ]);
        } catch (\Throwable) {
            // Ignore logging failures to not disrupt terminal workflow
        }
    }
}
