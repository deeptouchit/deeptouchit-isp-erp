<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminApiLogController extends Controller
{
    /**
     * Display REST API request logs and audit telemetry.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = ApiLog::with(['apiKey:id,name,key_prefix', 'apiUser:id,name,email,role']);

        // Search Filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhere('status_code', 'like', "%{$search}%")
                    ->orWhereHas('apiKey', function ($kq) use ($search) {
                        $kq->where('name', 'like', "%{$search}%")
                            ->orWhere('key_prefix', 'like', "%{$search}%");
                    })
                    ->orWhereHas('apiUser', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Method Filter
        if ($method = $request->input('method')) {
            if ($method !== 'all') {
                $query->where('method', strtoupper($method));
            }
        }

        // Status Tier Filter
        if ($status = $request->input('status')) {
            if ($status === '2xx') {
                $query->whereBetween('status_code', [200, 299]);
            } elseif ($status === '4xx') {
                $query->whereBetween('status_code', [400, 499]);
            } elseif ($status === '5xx') {
                $query->whereBetween('status_code', [500, 599]);
            } elseif ($status === '429') {
                $query->where('status_code', 429);
            }
        }

        $logs = $query->latest('id')
            ->paginate(20)
            ->withQueryString();

        // 4 Clean 3-Tier Metric Stats calculated live from DB
        $all = ApiLog::all();
        $totalLogs = $all->count();
        $successCount = $all->whereBetween('status_code', [200, 299])->count();
        $clientErrors = $all->whereBetween('status_code', [400, 499])->count();
        $successRate = $totalLogs > 0 ? round(($successCount / $totalLogs) * 100, 1) : 100.0;
        $avgLatency = (int) round($all->avg('duration_ms') ?? 0);

        $stats = [
            'total_requests' => $totalLogs,
            'success_count' => $successCount,
            'success_rate' => $successRate,
            'client_errors' => $clientErrors,
            'avg_latency' => $avgLatency,
        ];

        return Inertia::render('Admin/Api/Logs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['search', 'method', 'status']),
        ]);
    }

    /**
     * Show full payload and headers for single API request.
     */
    public function show(ApiLog $apiLog): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $apiLog->load(['apiKey:id,name,key_prefix', 'apiUser:id,name,email,role']);

        return response()->json($apiLog);
    }

    /**
     * Export filtered API logs to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $query = ApiLog::with(['apiKey:id,name', 'apiUser:id,name']);

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        if ($method = $request->input('method')) {
            if ($method !== 'all') {
                $query->where('method', strtoupper($method));
            }
        }

        $logs = $query->latest('id')->limit(1000)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="api_logs_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'Method', 'Endpoint', 'Status Code', 'Duration (ms)', 'IP Address', 'API Key / Identity', 'Error']);

            foreach ($logs as $log) {
                $identity = $log->apiKey?->name ?: ($log->apiUser?->name ?: 'Unauthenticated');
                fputcsv($file, [
                    $log->id,
                    $log->created_at->toDateTimeString(),
                    $log->method,
                    $log->endpoint,
                    $log->status_code,
                    $log->duration_ms,
                    $log->ip_address,
                    $identity,
                    $log->error_message ?: 'None',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear or purge old API logs.
     */
    public function clear(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $count = ApiLog::count();
        ApiLog::truncate();

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'api_logs_cleared',
            'description' => "Purged {$count} REST API log records.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['count' => $count],
            'new_values' => ['count' => 0],
        ]);

        return redirect()->route('admin.api.logs')
            ->with('success', "All {$count} API audit log entries purged successfully.");
    }
}
