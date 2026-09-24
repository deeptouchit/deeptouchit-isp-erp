<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Files\AdminStorageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStorageController extends Controller
{
    protected AdminStorageService $storageService;

    public function __construct(AdminStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Display Disk & Storage Manager Console.
     */
    public function index(): Response
    {
        $overview = $this->storageService->getStorageOverview();

        return Inertia::render('Admin/Files/Storage', [
            'primaryDisk' => $overview['primary_disk'],
            'inodes' => $overview['inodes'],
            'categories' => $overview['categories'],
            'subscriptions' => $overview['subscriptions'],
            'stats' => $overview['stats'],
        ]);
    }

    /**
     * Vacuum old system logs.
     */
    public function vacuumLogs()
    {
        $res = $this->storageService->vacuumSystemLogs(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Flush cache and temporary buffers.
     */
    public function flushCache()
    {
        $res = $this->storageService->flushTempCache(auth()->id());

        return redirect()->back()->with('success', $res['message']);
    }
}
