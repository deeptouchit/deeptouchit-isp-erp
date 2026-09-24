<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Automation\AutoSuspensionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminAutoSuspensionController extends Controller
{
    public function __construct(
        protected AutoSuspensionService $suspensionService
    ) {}

    /**
     * Display Auto-Suspension Policy and Overdue Queue Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status']);
        $data = $this->suspensionService->getSuspensionOverview($filters);

        return Inertia::render('Admin/Automation/AutoSuspension', [
            'stats' => $data['stats'],
            'subscriptions' => $data['subscriptions'],
            'filters' => $filters,
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Auto-Suspension.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status']);
        $data = $this->suspensionService->getSuspensionOverview($filters);

        return response()->json($data);
    }

    /**
     * Trigger immediate Auto-Suspension sweep for overdue subscriptions past grace period.
     */
    public function runSweep(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->suspensionService->runAutoSuspensionSweep(auth()->id());

        return response()->json($res);
    }

    /**
     * Manually suspend a subscription.
     */
    public function suspend(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $reason = $request->input('reason', 'Administrative quarantine');
        $res = $this->suspensionService->suspendSubscription($id, $reason, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Manually unsuspend / restore a subscription.
     */
    public function unsuspend(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->suspensionService->unsuspendSubscription($id, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Extend grace period for an overdue subscription.
     */
    public function extendGrace(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $days = (int)$request->input('days', 7);
        $res = $this->suspensionService->extendGracePeriod($id, $days, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }
}
