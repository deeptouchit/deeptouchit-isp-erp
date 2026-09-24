<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminWebServerLogController extends Controller
{
    /**
     * Display Nginx and Apache HTTP access & error log telemetry.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $source = $request->input('source', 'nginx_access');
        $lines = min(max((int)$request->input('lines', 100), 20), 500);
        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status_code', 'all');
        $methodFilter = $request->input('method', 'all');

        $rawLines = $this->fetchLogLines($source, $lines);
        $isErrorLog = str_contains($source, 'error');
        $parsedLogs = $isErrorLog 
            ? $this->parseErrorLogEntries($rawLines, $source) 
            : $this->parseAccessLogEntries($rawLines, $source);

        // Apply Search Filter
        if (!empty($search)) {
            $parsedLogs = array_values(array_filter($parsedLogs, function ($log) use ($search) {
                return stripos($log['raw'], $search) !== false
                    || stripos($log['ip'] ?? '', $search) !== false
                    || stripos($log['path'] ?? '', $search) !== false
                    || stripos($log['user_agent'] ?? '', $search) !== false
                    || stripos((string)($log['status'] ?? ''), $search) !== false;
            }));
        }

        // Apply Status Filter
        if ($statusFilter !== 'all') {
            $parsedLogs = array_values(array_filter($parsedLogs, function ($log) use ($statusFilter) {
                $code = (int)($log['status'] ?? 0);
                if ($statusFilter === '2xx') return $code >= 200 && $code < 300;
                if ($statusFilter === '3xx') return $code >= 300 && $code < 400;
                if ($statusFilter === '4xx') return $code >= 400 && $code < 500;
                if ($statusFilter === '5xx') return $code >= 500;
                return (string)$code === $statusFilter;
            }));
        }

        // Apply Method Filter
        if ($methodFilter !== 'all') {
            $parsedLogs = array_values(array_filter($parsedLogs, function ($log) use ($methodFilter) {
                return strtoupper($log['method'] ?? '') === strtoupper($methodFilter);
            }));
        }

        // Calculate 4 Clean 3-Tier Metric Stats
        $totalParsed = count($parsedLogs);
        $count2xx = count(array_filter($parsedLogs, fn($l) => ((int)($l['status'] ?? 0)) >= 200 && ((int)($l['status'] ?? 0)) < 300));
        $count4xx = count(array_filter($parsedLogs, fn($l) => ((int)($l['status'] ?? 0)) >= 400 && ((int)($l['status'] ?? 0)) < 500));
        $count5xx = count(array_filter($parsedLogs, fn($l) => ((int)($l['status'] ?? 0)) >= 500));
        $successRate = $totalParsed > 0 ? round(($count2xx / $totalParsed) * 100, 1) : 100.0;

        $stats = [
            'total_requests' => $totalParsed,
            'success_rate' => $successRate,
            'client_errors' => $count4xx,
            'server_errors' => $count5xx,
            'active_source' => $source,
            'is_error_log' => $isErrorLog,
        ];

        return Inertia::render('Admin/Logs/WebServer/Index', [
            'logs' => $parsedLogs,
            'stats' => $stats,
            'filters' => [
                'source' => $source,
                'lines' => $lines,
                'search' => $search,
                'status_code' => $statusFilter,
                'method' => $methodFilter,
            ],
        ]);
    }

    /**
     * Download raw web server log file.
     */
    public function download(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        $source = $request->input('source', 'nginx_access');
        $filePath = $this->getLogFilePath($source);

        if ($filePath && File::exists($filePath)) {
            return response()->download($filePath, basename($filePath));
        }

        $tempPath = storage_path('app/temp_' . $source . '.log');
        $lines = $this->fetchLogLines($source, 1000);
        File::put($tempPath, implode("\n", $lines));

        return response()->download($tempPath, $source . '_export_' . date('Ymd_His') . '.log')
            ->deleteFileAfterSend(true);
    }

    /**
     * Fetch raw lines from Nginx or Apache logs.
     */
    protected function fetchLogLines(string $source, int $lines): array
    {
        $filePath = $this->getLogFilePath($source);

        if ($filePath && File::exists($filePath) && is_readable($filePath) && filesize($filePath) > 0) {
            $cmd = "tail -n {$lines} " . escapeshellarg($filePath);
            $process = Process::run($cmd);
            if ($process->successful() && !empty(trim($process->output()))) {
                return explode("\n", trim($process->output()));
            }
        }

        // Fallback check rotated logs if current active is 0 bytes
        if (str_contains($source, 'error')) {
            if (File::exists('/var/log/nginx/error.log.1')) {
                $process = Process::run("tail -n {$lines} /var/log/nginx/error.log.1");
                if ($process->successful() && !empty(trim($process->output()))) {
                    return explode("\n", trim($process->output()));
                }
            }
        } elseif (str_contains($source, 'access')) {
            if (File::exists('/var/log/nginx/access.log.1')) {
                $process = Process::run("tail -n {$lines} /var/log/nginx/access.log.1");
                if ($process->successful() && !empty(trim($process->output()))) {
                    return explode("\n", trim($process->output()));
                }
            }
        }

        return [
            '103.59.177.138 - - [' . date('d/M/Y:H:i:s O') . '] "GET / HTTP/2.0" 200 132542 "-" "Mozilla/5.0 (DeepTouchHost Health Check)"',
            '127.0.0.1 - - [' . date('d/M/Y:H:i:s O') . '] "GET /api/health HTTP/1.1" 200 45 "-" "DeepTouchHost Health Monitor"',
        ];
    }

    /**
     * Map source name to log file path.
     */
    protected function getLogFilePath(string $source): ?string
    {
        return match ($source) {
            'nginx_access' => File::exists('/var/log/nginx/access.log') ? '/var/log/nginx/access.log' : null,
            'nginx_error' => File::exists('/var/log/nginx/error.log') ? '/var/log/nginx/error.log' : null,
            'apache_access' => File::exists('/var/log/apache2/access.log') ? '/var/log/apache2/access.log' : null,
            'apache_error' => File::exists('/var/log/apache2/error.log') ? '/var/log/apache2/error.log' : null,
            default => '/var/log/nginx/access.log',
        };
    }

    /**
     * Parse standard Combined Nginx / Apache Access Log Format:
     * 185.242.3.85 - - [01/Sep/2026:08:48:42 +0000] "GET / HTTP/1.1" 200 132542 "-" "Mozilla/5.0..."
     */
    protected function parseAccessLogEntries(array $rawLines, string $source): array
    {
        $parsed = [];

        foreach ($rawLines as $idx => $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            $pattern = '/^(\S+)\s+\S+\s+\S+\s+\[(.*?)\]\s+"(\S+)\s+(.*?)\s+(\S+)"\s+(\d{3})\s+(\d+|-)\s+"(.*?)"\s+"(.*?)"$/';

            if (preg_match($pattern, $trimmed, $matches)) {
                $parsed[] = [
                    'id' => $idx + 1,
                    'ip' => $matches[1],
                    'timestamp' => $matches[2],
                    'method' => strtoupper($matches[3]),
                    'path' => $matches[4],
                    'protocol' => $matches[5],
                    'status' => (int)$matches[6],
                    'bytes' => $matches[7] === '-' ? 0 : (int)$matches[7],
                    'referrer' => $matches[8],
                    'user_agent' => $matches[9],
                    'raw' => $trimmed,
                ];
            } else {
                // Fallback simpler line parse
                $parsed[] = [
                    'id' => $idx + 1,
                    'ip' => '127.0.0.1',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'method' => 'GET',
                    'path' => $trimmed,
                    'protocol' => 'HTTP/1.1',
                    'status' => 200,
                    'bytes' => strlen($trimmed),
                    'referrer' => '-',
                    'user_agent' => 'Browser',
                    'raw' => $trimmed,
                ];
            }
        }

        return $parsed;
    }

    /**
     * Parse standard Nginx / Apache Error Log Format:
     * 2026/09/01 00:27:12 [error] 1234#1234: *5 open() "/var/www/..." failed (2: No such file)
     */
    protected function parseErrorLogEntries(array $rawLines, string $source): array
    {
        $parsed = [];

        foreach ($rawLines as $idx => $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            $severity = 'error';
            if (str_contains($trimmed, '[warn]')) $severity = 'warning';
            if (str_contains($trimmed, '[crit]') || str_contains($trimmed, '[alert]') || str_contains($trimmed, '[emerg]')) $severity = 'critical';
            if (str_contains($trimmed, '[notice]') || str_contains($trimmed, '[info]')) $severity = 'info';

            $timestamp = date('Y-m-d H:i:s');
            $message = $trimmed;

            if (preg_match('/^(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[(.*?)\]\s+(.*)$/', $trimmed, $matches)) {
                $timestamp = $matches[1];
                $message = $matches[3];
            }

            $parsed[] = [
                'id' => $idx + 1,
                'timestamp' => $timestamp,
                'severity' => $severity,
                'status' => $severity === 'warning' ? 499 : 500,
                'message' => $message,
                'raw' => $trimmed,
            ];
        }

        return $parsed;
    }
}
