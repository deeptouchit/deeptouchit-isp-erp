<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupJob;
use App\Models\User;
use App\Services\Backups\AdminBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminBackupController extends Controller
{
    protected AdminBackupService $backupService;

    public function __construct(AdminBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display Backups & Disaster Recovery Dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'type', 'status']);
        $data = $this->backupService->getBackupOverview($filters);

        return Inertia::render('Admin/Backups/Index', [
            'backups' => $data['backups'],
            'stats' => $data['stats'],
            'subscriptions' => $data['subscriptions'],
            'filters' => $filters,
        ]);
    }

    /**
     * Display Dedicated Backup Jobs & Execution Monitor.
     */
    public function jobs(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'type', 'status']);
        $data = $this->backupService->getJobsOverview($filters);

        return Inertia::render('Admin/Backups/Jobs', [
            'jobs' => $data['jobs'],
            'stats' => $data['stats'],
            'subscriptions' => $data['subscriptions'],
            'filters' => $filters,
        ]);
    }

    /**
     * Display Disaster Recovery & Restore Hub.
     */
    public function restorePage(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'type']);
        $data = $this->backupService->getRestoreOverview($filters);

        return Inertia::render('Admin/Backups/Restore', [
            'snapshots' => $data['snapshots'],
            'stats' => $data['stats'],
            'recent_restores' => $data['recent_restores'],
            'subscriptions' => $data['subscriptions'],
            'filters' => $filters,
        ]);
    }

    /**
     * Display Unified Backup & Disaster Recovery Audit Logs.
     */
    public function logs(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'action']);
        $data = $this->backupService->getLogsOverview($filters);

        return Inertia::render('Admin/Backups/Logs', [
            'logs' => $data['logs'],
            'stats' => $data['stats'],
            'filters' => $filters,
        ]);
    }

    /**
     * Flush all historical backup activity logs.
     */
    public function flushLogs(): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->backupService->flushLogs(auth()->id());

        return redirect()->route('admin.backups.logs')->with('success', $res['message']);
    }

    /**
     * Create and dispatch a new backup snapshot.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'name' => 'nullable|string|max:255',
            'type' => 'required|in:full,files,database,incremental',
        ]);

        $res = $this->backupService->createBackup($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Retry an existing backup job.
     */
    public function retry(BackupJob $backupJob): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->backupService->retryJob($backupJob, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Download backup archive file.
     */
    public function download(BackupJob $backupJob): BinaryFileResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->backupService->download($backupJob);
    }

    /**
     * Restore a backup archive with optional safety rollback snapshot.
     */
    public function restore(Request $request, BackupJob $backupJob): RedirectResponse
    {
        $this->authorize('create', User::class);

        $options = [
            'create_safety_snapshot' => $request->boolean('create_safety_snapshot', true),
        ];

        $res = $this->backupService->restore($backupJob, $options, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Upload and restore external backup archive.
     */
    public function uploadAndRestore(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'archive_file' => 'required|file|max:512000', // 500MB
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'type' => 'required|in:full,files,database',
            'create_safety_snapshot' => 'nullable|boolean',
        ]);

        $file = $request->file('archive_file');
        $res = $this->backupService->uploadAndRestore($file, $request->only(['subscription_id', 'type', 'create_safety_snapshot']), auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete a backup archive.
     */
    public function destroy(BackupJob $backupJob): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->backupService->delete($backupJob, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
