<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Automation\CronAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCronController extends Controller
{
    public function __construct(
        protected CronAutomationService $cronService
    ) {}

    /**
     * Display Cron automation manager dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->cronService->getCronOverview();

        return Inertia::render('Admin/Automation/Cron', [
            'stats' => $data['stats'],
            'jobs' => $data['jobs'],
            'system_crons' => $data['system_crons'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Cron dashboard.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->cronService->getCronOverview();

        return response()->json($data);
    }

    /**
     * Store a newly created cron job.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'command' => 'required|string',
            'cron_expression' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'output_handling' => 'required|string|in:discard,log_file,email',
            'log_file_path' => 'nullable|string|max:255',
            'run_as_user' => 'required|string|max:50',
            'is_enabled' => 'boolean',
        ]);

        $res = $this->cronService->createJob($validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Update an existing scheduled cron job.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'command' => 'required|string',
            'cron_expression' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'output_handling' => 'required|string|in:discard,log_file,email',
            'log_file_path' => 'nullable|string|max:255',
            'run_as_user' => 'required|string|max:50',
            'is_enabled' => 'boolean',
        ]);

        $res = $this->cronService->updateJob($id, $validated, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Delete a scheduled cron job.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->cronService->deleteJob($id, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Toggle enabled state of a cron job.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        $this->authorize('create', User::class);

        $res = $this->cronService->toggleJob($id, auth()->id());

        if (!$res['success']) {
            return redirect()->back()->with('error', $res['message']);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Run a scheduled cron job immediately on-demand.
     */
    public function runNow(Request $request, int $id): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->cronService->runJobNow($id, auth()->id());

        return response()->json($res);
    }

    /**
     * Fetch execution logs for a specific cron job.
     */
    public function logs(Request $request, int $id): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->cronService->getJobLogs($id, 25);

        return response()->json($data);
    }
}
