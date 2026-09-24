<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Automation\MaintenanceAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMaintenanceController extends Controller
{
    public function __construct(
        protected MaintenanceAutomationService $maintenanceService
    ) {}

    /**
     * Display Application & Infrastructure Maintenance Center.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->maintenanceService->getMaintenanceOverview();

        return Inertia::render('Admin/Automation/Maintenance', [
            'stats' => $data['stats'],
            'downData' => $data['down_data'],
            'servers' => $data['servers'],
            'routines' => $data['routines'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Maintenance.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->maintenanceService->getMaintenanceOverview();

        return response()->json($data);
    }

    /**
     * Enable system maintenance mode.
     */
    public function enable(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'secret' => 'nullable|string|max:100',
            'retry' => 'nullable|integer|min:30|max:86400',
            'status' => 'nullable|integer|in:503,403',
        ]);

        $res = $this->maintenanceService->enableMaintenance($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Disable system maintenance mode.
     */
    public function disable(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->maintenanceService->disableMaintenance(auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Execute an automated maintenance or optimization routine.
     */
    public function runRoutine(Request $request, string $routineId): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->maintenanceService->executeRoutine($routineId, auth()->id());

        return response()->json($res);
    }

    /**
     * Toggle Maintenance mode on a server node.
     */
    public function toggleServer(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->maintenanceService->toggleServerMaintenance($id, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
