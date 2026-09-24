<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Security\IpBlockService;
use App\Services\Security\SecurityEventsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SecurityEventsController extends Controller
{
    protected SecurityEventsService $securityEventsService;

    public function __construct(SecurityEventsService $securityEventsService)
    {
        $this->securityEventsService = $securityEventsService;
    }

    /**
     * Display Security Events & Audit Trail console.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $action = $request->query('action', 'all');
        $tab = $request->query('view', 'audit'); // audit or system

        $auditLogs = $this->securityEventsService->getAuditEvents([
            'search' => $search,
            'action' => $action,
        ]);

        $systemEvents = $this->securityEventsService->getSystemAuthEvents(30);
        $metrics = $this->securityEventsService->getMetrics();

        return Inertia::render('Admin/Security/Events', [
            'auditLogs' => $auditLogs,
            'systemEvents' => $systemEvents,
            'metrics' => $metrics,
            'filters' => [
                'search' => $search,
                'action' => $action,
                'view' => $tab,
            ],
        ]);
    }

    /**
     * Purge old audit logs.
     */
    public function purge(Request $request)
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:7|max:365',
        ]);

        $res = $this->securityEventsService->purgeLogs($validated['days'], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * 1-Click Block IP from Live Event Stream.
     */
    public function quickBlockIp(Request $request)
    {
        $validated = $request->validate([
            'ip' => 'required|string|max:45',
            'reason' => 'nullable|string|max:255',
        ]);

        $blockService = app(IpBlockService::class);
        $res = $blockService->blockIp([
            'ip_address' => $validated['ip'],
            'reason' => $validated['reason'] ?? 'Blocked from live security event stream',
            'duration' => 'permanent',
            'type' => 'system',
        ], auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Export Audit Trail to CSV.
     */
    public function export(): HttpResponse
    {
        $logs = ActivityLog::with('user')->latest()->limit(5000)->get();

        $csv = "ID,Timestamp,User,Action,Description,IP Address,User Agent\n";
        foreach ($logs as $log) {
            $user = $log->user ? $log->user->name : 'System/Guest';
            $desc = str_replace('"', '""', $log->description);
            $csv .= "\"{$log->id}\",\"{$log->created_at}\",\"{$user}\",\"{$log->action}\",\"{$desc}\",\"{$log->ip_address}\",\"{$log->user_agent}\"\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="deeptouchhost-security-audit-trail.csv"',
        ]);
    }
}
