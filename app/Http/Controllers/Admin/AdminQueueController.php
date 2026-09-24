<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Automation\QueueAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminQueueController extends Controller
{
    public function __construct(
        protected QueueAutomationService $queueService
    ) {}

    /**
     * Display Queue Workers & Jobs Management Dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $data = $this->queueService->getQueueOverview();

        return Inertia::render('Admin/Automation/Queues', [
            'stats' => $data['stats'],
            'supervisor' => $data['supervisor'],
            'workers' => $data['workers'],
            'pools' => $data['pools'],
            'pending_jobs' => $data['pending_jobs'],
            'failed_jobs' => $data['failed_jobs'],
            'batches' => $data['batches'],
        ]);
    }

    /**
     * Fast live JSON telemetry polling endpoint for Queue dashboard.
     */
    public function apiMetrics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = $this->queueService->getQueueOverview();

        return response()->json($data);
    }

    /**
     * Retry a specific failed job.
     */
    public function retry(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'uuid' => 'required|string',
        ]);

        $res = $this->queueService->retryFailedJob($request->input('uuid'), auth()->id());

        return response()->json($res);
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->queueService->retryAllFailedJobs(auth()->id());

        return response()->json($res);
    }

    /**
     * Forget / remove a failed job.
     */
    public function forget(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'uuid' => 'required|string',
        ]);

        $res = $this->queueService->forgetFailedJob($request->input('uuid'), auth()->id());

        return response()->json($res);
    }

    /**
     * Flush all failed jobs.
     */
    public function flush(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->queueService->flushAllFailedJobs(auth()->id());

        return response()->json($res);
    }

    /**
     * Restart all queue workers gracefully.
     */
    public function restartWorkers(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $res = $this->queueService->restartQueueWorkers(auth()->id());

        return response()->json($res);
    }

    /**
     * Dispatch a test benchmark job.
     */
    public function dispatchTest(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $queue = $request->input('queue', 'default');
        $res = $this->queueService->dispatchTestJob($queue, auth()->id());

        return response()->json($res);
    }
}
