<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupStorage;
use App\Models\User;
use App\Services\Backups\AdminBackupStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBackupStorageController extends Controller
{
    protected AdminBackupStorageService $storageService;

    public function __construct(AdminBackupStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Display Backup Storage Repositories & Cloud Destinations Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'driver', 'status']);
        $data = $this->storageService->getStorageOverview($filters);

        return Inertia::render('Admin/Backups/Storage', [
            'storages' => $data['storages'],
            'stats' => $data['stats'],
            'filters' => $filters,
        ]);
    }

    /**
     * Register a new storage destination.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:local,s3,r2,wasabi,sftp,google_drive',
            'path' => 'required|string|max:255',
            'capacity_gb' => 'nullable|integer|min:1',
            'retention_days' => 'nullable|integer|min:1',
            'is_default' => 'nullable|boolean',
            'encryption_enabled' => 'nullable|boolean',
            'credentials' => 'nullable|array',
        ]);

        $res = $this->storageService->store($validated, auth()->id());

        return redirect()->route('admin.backups.storage')->with('success', $res['message']);
    }

    /**
     * Update storage repository configuration.
     */
    public function update(Request $request, BackupStorage $backupStorage): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => 'required|in:local,s3,r2,wasabi,sftp,google_drive',
            'path' => 'required|string|max:255',
            'capacity_gb' => 'nullable|integer|min:1',
            'retention_days' => 'nullable|integer|min:1',
            'is_default' => 'nullable|boolean',
            'encryption_enabled' => 'nullable|boolean',
            'credentials' => 'nullable|array',
        ]);

        $res = $this->storageService->update($backupStorage, $validated, auth()->id());

        return redirect()->route('admin.backups.storage')->with('success', $res['message']);
    }

    /**
     * Test connection to storage destination.
     */
    public function test(BackupStorage $backupStorage): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->storageService->testConnection($backupStorage, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Set repository as primary default destination.
     */
    public function setDefault(BackupStorage $backupStorage): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->storageService->setDefault($backupStorage, auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete storage repository.
     */
    public function destroy(BackupStorage $backupStorage): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->storageService->destroy($backupStorage, auth()->id());

        return redirect()->route('admin.backups.storage')->with('success', $res['message']);
    }
}
