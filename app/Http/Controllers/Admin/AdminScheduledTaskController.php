<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Automation\ScheduledTaskAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminScheduledTaskController extends Controller
{
    public function __construct(
        protected ScheduledTaskAutomationService $taskService
    ) {}

    /**
     * Display Kernel Scheduled Tasks Dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->taskService->getScheduledTasksOverview();

        return Inertia::render('Admin/Automation/ScheduledTasks', [
            'stats' => $data['stats'],
            'tasks' => $data['tasks'],
            'categories' => $data['categories'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Scheduled Tasks.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->taskService->getScheduledTasksOverview();

        return response()->json($data);
    }

    /**
     * Execute a specific scheduled task on-demand immediately.
     */
    public function runNow(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'command' => 'required|string',
        ]);

        $res = $this->taskService->runTaskNow($request->input('command'), auth()->id());

        return response()->json($res);
    }

    /**
     * Trigger full schedule dispatcher cycle on-demand.
     */
    public function runSchedule(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->taskService->runFullScheduleDispatch(auth()->id());

        return response()->json($res);
    }
}
