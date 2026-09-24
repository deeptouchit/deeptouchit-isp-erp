<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Email\EmailLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailLogController extends Controller
{
    protected EmailLogService $logService;

    public function __construct(EmailLogService $logService)
    {
        $this->logService = $logService;
    }

    /**
     * Display Email Server Daemon Logs & Admin Audit Trail.
     */
    public function index(Request $request): Response
    {
        $viewMode = $request->query('mode', 'daemon'); // 'daemon' | 'audit'
        $component = $request->query('component', 'all');
        $search = $request->query('search', '');
        $limit = (int)$request->query('limit', 150);

        $daemonData = $this->logService->getDaemonLogs($limit, $component, $search);
        $auditLogs = ($viewMode === 'audit') ? $this->logService->getAuditLogs($limit, $search) : [];

        return Inertia::render('Admin/Email/Logs', [
            'logs' => $daemonData['entries'],
            'stats' => $daemonData['stats'],
            'auditLogs' => $auditLogs,
            'currentMode' => $viewMode,
            'currentComponent' => $component,
            'search' => $search,
            'limit' => $limit,
        ]);
    }

    /**
     * Truncate mail server logs.
     */
    public function clear(Request $request)
    {
        $res = $this->logService->clearMailLogs(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
