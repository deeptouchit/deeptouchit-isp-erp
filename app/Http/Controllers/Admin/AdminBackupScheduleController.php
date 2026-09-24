<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupSchedule;
use App\Models\User;
use App\Services\Backups\AdminBackupScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBackupScheduleController extends Controller
{
    protected AdminBackupScheduleService $scheduleService;

    public function __construct(AdminBackupScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Display Automated Backup Schedules Orchestrator.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'frequency', 'status']);
        $data = $this->scheduleService->getSchedulesOverview($filters);

        $view = ($request->routeIs('admin.backups.schedules') || $request->is('admin/backups*'))
            ? 'Admin/Backups/Schedules'
            : 'Admin/Automation/AutoBackup';

        return Inertia::render($view, [
            'schedules' => $data['schedules'],
            'stats' => $data['stats'],
            'storages' => $data['storages'],
            'subscriptions' => $data['subscriptions'],
            'filters' => $filters,
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Auto-Backup schedules.
     */
    public function apiMetrics(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'frequency', 'status']);
        $data = $this->scheduleService->getSchedulesOverview($filters);

        return response()->json($data);
    }

    /**
     * Create a new automated recurring schedule.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'backup_storage_id' => 'nullable|exists:backup_storages,id',
            'frequency' => 'required|in:hourly,daily,weekly,monthly,custom',
            'cron_expression' => 'nullable|string|max:100',
            'type' => 'required|in:full,files,database,incremental',
            'retention_count' => 'nullable|integer|min:1|max:365',
            'status' => 'nullable|in:active,paused',
            'notify_on_failure' => 'nullable|boolean',
        ]);

        $res = $this->scheduleService->store($validated, auth()->id());

        return redirect()->route('admin.backups.schedules')->with('success', $res['message']);
    }

    /**
     * Update backup schedule configuration.
     */
    public function update(Request $request, BackupSchedule $backupSchedule): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'backup_storage_id' => 'nullable|exists:backup_storages,id',
            'frequency' => 'required|in:hourly,daily,weekly,monthly,custom',
            'cron_expression' => 'nullable|string|max:100',
            'type' => 'required|in:full,files,database,incremental',
            'retention_count' => 'nullable|integer|min:1|max:365',
            'status' => 'nullable|in:active,paused',
            'notify_on_failure' => 'nullable|boolean',
        ]);

        $res = $this->scheduleService->update($backupSchedule, $validated, auth()->id());

        return redirect()->route('admin.backups.schedules')->with('success', $res['message']);
    }

    /**
     * Toggle active/paused schedule state.
     */
    public function toggle(BackupSchedule $backupSchedule): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->scheduleService->toggleStatus($backupSchedule, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Manually trigger immediate execution of schedule.
     */
    public function runNow(BackupSchedule $backupSchedule): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->scheduleService->runNow($backupSchedule, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete schedule.
     */
    public function destroy(BackupSchedule $backupSchedule): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->scheduleService->destroy($backupSchedule, auth()->id());

        return redirect()->route('admin.backups.schedules')->with('success', $res['message']);
    }
}
