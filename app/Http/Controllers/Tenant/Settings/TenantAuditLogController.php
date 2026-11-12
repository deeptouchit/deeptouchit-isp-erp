<?php

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAuditLogController extends Controller
{
    /**
     * Get active tenant
     */
    protected function getTenant(): Tenant
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id ?? 1;
        return Tenant::find($tenantId) ?? Tenant::first();
    }

    /**
     * Activity & Audit Trail Master View
     */
    public function index(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $search = $request->input('search');
        $actorFilter = $request->input('actor_type', 'all');
        $eventFilter = $request->input('event_type', 'all');
        $categoryFilter = $request->input('category', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int)$request->input('per_page', 20);

        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        // Base Query
        $query = TenantActivityLog::where('tenant_id', $tenantId);

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Actor Filter
        if ($actorFilter !== 'all' && !empty($actorFilter)) {
            $query->where('actor_type', $actorFilter);
        }

        // Event Type Filter
        if ($eventFilter !== 'all' && !empty($eventFilter)) {
            $query->where('event_type', $eventFilter);
        }

        // Category Filter
        if ($categoryFilter !== 'all' && !empty($categoryFilter)) {
            match ($categoryFilter) {
                'security' => $query->where(function ($q) {
                    $q->where('event_type', 'like', '%AUTH%')
                      ->orWhere('event_type', 'like', '%LOGIN%')
                      ->orWhere('event_type', 'like', '%LOGOUT%')
                      ->orWhere('event_type', 'like', '%PASSWORD%');
                }),
                'finance' => $query->where(function ($q) {
                    $q->where('event_type', 'like', '%BILL%')
                      ->orWhere('event_type', 'like', '%INVOICE%')
                      ->orWhere('event_type', 'like', '%PAYMENT%')
                      ->orWhere('event_type', 'like', '%WALLET%')
                      ->orWhere('event_type', 'like', '%EXPENSE%');
                }),
                'network' => $query->where(function ($q) {
                    $q->where('event_type', 'like', '%NETWORK%')
                      ->orWhere('event_type', 'like', '%MIKROTIK%')
                      ->orWhere('event_type', 'like', '%ROUTER%')
                      ->orWhere('event_type', 'like', '%ONU%')
                      ->orWhere('event_type', 'like', '%OLT%');
                }),
                'system' => $query->where(function ($q) {
                    $q->where('event_type', 'like', '%BACKUP%')
                      ->orWhere('event_type', 'like', '%MAINTENANCE%')
                      ->orWhere('event_type', 'like', '%CACHE%')
                      ->orWhere('event_type', 'like', '%ROLE%')
                      ->orWhere('event_type', 'like', '%SETTING%');
                }),
                default => null,
            };
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest('id')->paginate($perPage)->withQueryString();

        // 6 KPI Summary Metric Cards (AGENTS.md Rule 2.B)
        $baseLogQuery = TenantActivityLog::where('tenant_id', $tenantId);
        $totalLogsCount = (clone $baseLogQuery)->count();
        $todayLogsCount = (clone $baseLogQuery)->whereDate('created_at', Carbon::today()->toDateString())->count();

        $securityCount = (clone $baseLogQuery)->where(function ($q) {
            $q->where('event_type', 'like', '%AUTH%')
              ->orWhere('event_type', 'like', '%LOGIN%')
              ->orWhere('event_type', 'like', '%PASSWORD%');
        })->count();

        $financeCount = (clone $baseLogQuery)->where(function ($q) {
            $q->where('event_type', 'like', '%BILL%')
              ->orWhere('event_type', 'like', '%INVOICE%')
              ->orWhere('event_type', 'like', '%PAYMENT%')
              ->orWhere('event_type', 'like', '%WALLET%');
        })->count();

        $networkCount = (clone $baseLogQuery)->where(function ($q) {
            $q->where('event_type', 'like', '%NETWORK%')
              ->orWhere('event_type', 'like', '%MIKROTIK%')
              ->orWhere('event_type', 'like', '%ROUTER%')
              ->orWhere('event_type', 'like', '%ONU%');
        })->count();

        $systemCount = (clone $baseLogQuery)->where(function ($q) {
            $q->where('event_type', 'like', '%BACKUP%')
              ->orWhere('event_type', 'like', '%MAINTENANCE%')
              ->orWhere('event_type', 'like', '%ROLE%')
              ->orWhere('event_type', 'like', '%AUTOMATION%');
        })->count();

        $stats = [
            'total_events' => number_format($totalLogsCount),
            'today_events' => number_format($todayLogsCount),
            'security_events' => number_format($securityCount),
            'finance_events' => number_format($financeCount),
            'network_events' => number_format($networkCount),
            'system_events' => number_format($systemCount),
        ];

        // Dropdown distinct event types
        $distinctEvents = TenantActivityLog::where('tenant_id', $tenantId)
            ->distinct('event_type')
            ->pluck('event_type')
            ->filter()
            ->values();

        // Dropdown distinct actors
        $distinctActors = TenantActivityLog::where('tenant_id', $tenantId)
            ->distinct('actor_type')
            ->pluck('actor_type')
            ->filter()
            ->values();

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.settings.audit_logs', compact(
            'tenant',
            'currencySymbol',
            'logs',
            'stats',
            'search',
            'actorFilter',
            'eventFilter',
            'categoryFilter',
            'dateFrom',
            'dateTo',
            'perPage',
            'distinctEvents',
            'distinctActors'
        ));
    }

    /**
     * Show single log JSON telemetry
     */
    public function show($id): JsonResponse
    {
        $tenant = $this->getTenant();
        $log = TenantActivityLog::where('tenant_id', $tenant->id)->findOrFail($id);
        $device = $log->device;
        $device['browser'] = __($device['browser']);
        $device['platform'] = __($device['platform']);

        return response()->json([
            'success' => true,
            'log' => [
                'id' => $log->id,
                'actor_name' => $log->actor_name ?? __('System Daemon'),
                'actor_type' => $log->actor_type ?? 'system',
                'actor_badge' => $log->actor_badge,
                'event_type' => $log->event_type,
                'event_badge' => $log->event_type_badge,
                'description' => $log->description,
                'ip_address' => $log->ip_address ?? '127.0.0.1',
                'user_agent' => $log->user_agent ?? __('N/A'),
                'device' => $device,
                'metadata' => $log->metadata ?? [],
                'created_at' => $log->created_at ? $log->created_at->translatedFormat('d-M-Y h:i:s A') : __('N/A'),
                'relative_time' => $log->relative_time,
            ]
        ]);
    }

    /**
     * Delete an individual log entry
     */
    public function destroy($id): RedirectResponse|JsonResponse
    {
        $tenant = $this->getTenant();
        $log = TenantActivityLog::where('tenant_id', $tenant->id)->findOrFail($id);
        $logId = $log->id;
        $eventType = $log->event_type;
        $log->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Audit log entry #:id (:event) deleted successfully.', ['id' => $logId, 'event' => $eventType])
            ]);
        }

        return back()->with('success', __('Audit log entry #:id deleted successfully.', ['id' => $logId]));
    }

    /**
     * Purge legacy logs older than N days
     */
    public function purge(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->getTenant();
        $days = (int)$request->input('days', 90);

        if (!in_array($days, [30, 60, 90, 180, 365])) {
            $days = 90;
        }

        $cutoffDate = Carbon::now()->subDays($days);
        $deletedCount = TenantActivityLog::where('tenant_id', $tenant->id)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        // Log the purge activity
        TenantActivityLog::create([
            'tenant_id' => $tenant->id,
            'actor_type' => 'tenant',
            'actor_id' => Auth::id(),
            'actor_name' => Auth::user()->name ?? __('ISP Administrator'),
            'event_type' => 'AUDIT_LOGS_PURGED',
            'description' => __('Purged :count historical audit log entries older than :days days.', ['count' => $deletedCount, 'days' => $days]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'purged_count' => $deletedCount,
                'retention_days' => $days,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ],
        ]);

        $msg = __('Purged :count audit log records older than :days days successfully.', ['count' => $deletedCount, 'days' => $days]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'purged_count' => $deletedCount,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Export Audit Trail to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $search = $request->input('search');
        $actorFilter = $request->input('actor_type', 'all');
        $eventFilter = $request->input('event_type', 'all');
        $categoryFilter = $request->input('category', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = TenantActivityLog::where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($actorFilter !== 'all' && !empty($actorFilter)) {
            $query->where('actor_type', $actorFilter);
        }

        if ($eventFilter !== 'all' && !empty($eventFilter)) {
            $query->where('event_type', $eventFilter);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest('id')->limit(5000)->get();
        $filename = 'Audit_Trail_' . preg_replace('/[^A-Za-z0-9]/', '_', $tenant->company_name ?? $tenant->name) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                __('ID'),
                __('Timestamp (UTC+6)'),
                __('Actor Name'),
                __('Actor Type'),
                __('Event Type'),
                __('Description'),
                __('IP Address'),
                __('User Agent'),
                __('Metadata Payload'),
            ]);

            foreach ($logs as $l) {
                fputcsv($handle, [
                    $l->id,
                    $l->created_at ? $l->created_at->translatedFormat('d-M-Y h:i:s A') : __('N/A'),
                    $l->actor_name ?? __('System'),
                    $l->actor_badge['label'] ?? ($l->actor_type ?? 'system'),
                    $l->event_type,
                    $l->description,
                    $l->ip_address,
                    $l->user_agent,
                    json_encode($l->metadata, JSON_UNESCAPED_SLASHES),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Printable A4 Audit Trail Statement
     */
    public function printReport(Request $request): View
    {
        $tenant = $this->getTenant();
        $tenantId = $tenant->id;

        $search = $request->input('search');
        $actorFilter = $request->input('actor_type', 'all');
        $eventFilter = $request->input('event_type', 'all');
        $categoryFilter = $request->input('category', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = TenantActivityLog::where('tenant_id', $tenantId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('event_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($actorFilter !== 'all' && !empty($actorFilter)) {
            $query->where('actor_type', $actorFilter);
        }

        if ($eventFilter !== 'all' && !empty($eventFilter)) {
            $query->where('event_type', $eventFilter);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs = $query->latest('id')->limit(300)->get();

        $stats = [
            'total_events' => TenantActivityLog::where('tenant_id', $tenantId)->count(),
            'today_events' => TenantActivityLog::where('tenant_id', $tenantId)->whereDate('created_at', Carbon::today()->toDateString())->count(),
            'security_events' => TenantActivityLog::where('tenant_id', $tenantId)->where('event_type', 'like', '%AUTH%')->count(),
            'printed_at' => Carbon::now()->translatedFormat('d-M-Y h:i:s A'),
            'printed_by' => Auth::user()->name ?? __('ISP Administrator'),
        ];

        $currencySymbol = $tenant->currency_symbol ?? '৳';

        return view('tenant.settings.audit_print', compact('tenant', 'currencySymbol', 'logs', 'stats'));
    }
}
