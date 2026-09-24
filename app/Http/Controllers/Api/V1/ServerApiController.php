<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\ApproveHostKeyRequest;
use App\Http\Requests\Admin\Servers\ManageServerServiceRequest;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Http\Requests\Admin\Servers\ToggleMaintenanceRequest;
use App\Http\Requests\Admin\Servers\UpdateServerRequest;
use App\Http\Resources\ServerDetailResource;
use App\Http\Resources\ServerResource;
use App\Jobs\Infrastructure\Servers\DiscoverServerJob;
use App\Jobs\Infrastructure\Servers\SyncServerJob;
use App\Jobs\Infrastructure\Servers\VerifyServerJob;
use App\Models\Server;
use App\Services\Infrastructure\Servers\ServerEventService;
use App\Services\Infrastructure\Servers\ServerService;
use App\Services\Infrastructure\Servers\ServerServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServerApiController extends Controller
{
    protected ServerService $serverService;
    protected ServerEventService $eventService;

    public function __construct(ServerService $serverService, ServerEventService $eventService)
    {
        $this->serverService = $serverService;
        $this->eventService = $eventService;
    }

    /**
     * List all servers with filtering, sorting, and bounded pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Server::class);

        $query = Server::query()->with(['serverGroup', 'latestMetric']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($health = $request->input('health_status')) {
            $query->where('health_status', $health);
        }
        if ($env = $request->input('environment')) {
            $query->where('environment', $env);
        }

        $allowedSorts = ['name', 'hostname', 'ip_address', 'status', 'health_status', 'created_at', 'updated_at'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'created_at';
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $direction);

        $perPage = min(100, max(5, (int) $request->input('per_page', 15)));

        return ServerResource::collection($query->paginate($perPage));
    }

    /**
     * Show detailed server entity without credentials.
     */
    public function show(Server $server): ServerDetailResource
    {
        $this->authorize('view', $server);

        $server->load([
            'serverGroup',
            'latestMetric',
            'services',
            'events' => fn ($q) => $q->latest()->take(25),
            'logs' => fn ($q) => $q->latest()->take(25),
        ]);

        $server->loadCount('subscriptions');

        return new ServerDetailResource($server);
    }

    /**
     * Store new server and trigger verification.
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $server = $this->serverService->registerServer($request->validated());

        VerifyServerJob::dispatch($server);

        return response()->json([
            'success' => true,
            'message' => 'Server registered successfully. Verification queued.',
            'data' => new ServerResource($server),
        ], 201);
    }

    /**
     * Update server metadata.
     */
    public function update(UpdateServerRequest $request, Server $server): JsonResponse
    {
        $this->serverService->updateServer($server, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Server updated successfully.',
            'data' => new ServerResource($server->fresh(['serverGroup', 'latestMetric'])),
        ]);
    }

    /**
     * Decommission / Delete server node.
     */
    public function destroy(Server $server): JsonResponse
    {
        $this->authorize('delete', $server);

        if ($server->is_master) {
            return response()->json([
                'success' => false,
                'error' => 'MASTER_NODE_PROTECTED',
                'message' => 'Cannot delete the cluster Master control plane node.',
            ], 409);
        }

        $activeSubscriptions = $server->subscriptions()->count();
        if ($activeSubscriptions > 0) {
            return response()->json([
                'success' => false,
                'error' => 'ACTIVE_ACCOUNTS_DEPENDENCY',
                'message' => "Cannot delete server: {$activeSubscriptions} active hosting account(s) depend on this node.",
            ], 409);
        }

        $this->serverService->deleteServer($server);

        return response()->json([
            'success' => true,
            'message' => 'Server decommissioned successfully.',
        ]);
    }

    /**
     * Asynchronously trigger connectivity verification.
     */
    public function verify(Server $server): JsonResponse
    {
        $this->authorize('verify', $server);

        VerifyServerJob::dispatch($server);

        return response()->json([
            'success' => true,
            'message' => 'Server verification queued.',
            'server_uuid' => $server->uuid,
        ], 202);
    }

    /**
     * Asynchronously trigger discovery.
     */
    public function discover(Server $server): JsonResponse
    {
        $this->authorize('discover', $server);

        DiscoverServerJob::dispatch($server);

        return response()->json([
            'success' => true,
            'message' => 'Server hardware discovery queued.',
            'server_uuid' => $server->uuid,
        ], 202);
    }

    /**
     * Asynchronously trigger sync.
     */
    public function sync(Server $server): JsonResponse
    {
        $this->authorize('sync', $server);

        SyncServerJob::dispatch($server);

        return response()->json([
            'success' => true,
            'message' => 'Server cluster synchronization queued.',
            'server_uuid' => $server->uuid,
        ], 202);
    }

    /**
     * Toggle maintenance mode.
     */
    public function maintenance(ToggleMaintenanceRequest $request, Server $server): JsonResponse
    {
        $this->authorize('maintenance', $server);

        $this->serverService->setMaintenanceMode(
            $server,
            $request->boolean('enabled'),
            $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Maintenance mode updated.',
            'data' => new ServerResource($server->fresh()),
        ]);
    }

    /**
     * Manage allowlisted systemd services.
     */
    public function manageService(
        ManageServerServiceRequest $request,
        Server $server,
        ServerServiceManager $serviceManager
    ): JsonResponse {
        $this->authorize('manageServices', $server);

        $result = $serviceManager->manageService(
            $server,
            $request->input('service'),
            $request->input('action')
        );

        return response()->json([
            'success' => true,
            'message' => "Service operation executed successfully.",
            'result' => $result,
        ]);
    }

    /**
     * Approve discovered SSH host key fingerprint.
     */
    public function approveHostKey(ApproveHostKeyRequest $request, Server $server): JsonResponse
    {
        $this->authorize('approveHostKey', $server);

        $fingerprint = $request->input('fingerprint');
        $oldFingerprint = $server->trusted_ssh_host_key_fingerprint;

        $server->update([
            'trusted_ssh_host_key_fingerprint' => $fingerprint,
        ]);

        $this->eventService->recordEvent(
            $server,
            ServerEventType::SERVER_VERIFIED,
            "SSH Host key fingerprint approved via API by administrator. Fingerprint: {$fingerprint}",
            ServerEventSeverity::INFO,
            ['old_fingerprint' => $oldFingerprint, 'new_fingerprint' => $fingerprint]
        );

        return response()->json([
            'success' => true,
            'message' => 'SSH Host key fingerprint approved.',
            'fingerprint' => $fingerprint,
        ]);
    }
}
