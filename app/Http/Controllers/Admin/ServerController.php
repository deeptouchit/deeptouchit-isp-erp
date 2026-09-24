<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerCredentialType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerEventSeverity;
use App\Enums\Infrastructure\ServerEventType;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\Servers\RemoteOperation;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Servers\ApproveHostKeyRequest;
use App\Http\Requests\Admin\Servers\ManageServerServiceRequest;
use App\Http\Requests\Admin\Servers\RotateCredentialRequest;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Http\Requests\Admin\Servers\ToggleMaintenanceRequest;
use App\Http\Requests\Admin\Servers\UpdateServerRequest;
use App\Jobs\Infrastructure\Servers\DiscoverServerJob;
use App\Jobs\Infrastructure\Servers\SyncServerJob;
use App\Jobs\Infrastructure\Servers\VerifyServerJob;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Services\Infrastructure\Servers\ServerCredentialService;
use App\Services\Infrastructure\Servers\ServerEventService;
use App\Services\Infrastructure\Servers\ServerService;
use App\Services\Infrastructure\Servers\ServerServiceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    protected ServerService $serverService;
    protected ServerEventService $eventService;

    public function __construct(ServerService $serverService, ServerEventService $eventService)
    {
        $this->serverService = $serverService;
        $this->eventService = $eventService;
    }

    /**
     * Display a paginated list of servers with filters, search, and sorting.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Server::class);

        $query = Server::query()->with(['serverGroup', 'latestMetric']);

        // 1. Search filter
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('primary_ip', 'like', "%{$search}%");
            });
        }

        // 2. Specific filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($health = $request->input('health_status')) {
            $query->where('health_status', $health);
        }
        if ($type = $request->input('server_type')) {
            $query->where('server_type', $type);
        }
        if ($env = $request->input('environment')) {
            $query->where('environment', $env);
        }
        if ($groupId = $request->input('server_group_id')) {
            $query->where('server_group_id', $groupId);
        }

        // 3. Allowlisted sorting
        $allowedSorts = ['name', 'hostname', 'ip_address', 'status', 'health_status', 'created_at', 'updated_at'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'created_at';
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $direction);

        // 4. Bounded pagination (max 100)
        $perPage = min(100, max(5, (int) $request->input('per_page', 15)));
        $servers = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Server::count(),
            'online' => Server::whereIn('status', [ServerStatus::ACTIVE, ServerStatus::ONLINE, ServerStatus::VERIFIED])->count(),
            'maintenance' => Server::where('status', ServerStatus::MAINTENANCE)->count(),
            'pending' => Server::whereIn('status', [ServerStatus::PENDING, ServerStatus::VERIFYING])->count(),
            'warning' => Server::whereIn('health_status', [ServerHealthStatus::WARNING, ServerHealthStatus::CRITICAL])->count(),
        ];

        return Inertia::render('Admin/Servers/Index', [
            'servers' => $servers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'health_status', 'server_type', 'environment', 'server_group_id', 'sort', 'direction', 'per_page']),
            'serverGroups' => ServerGroup::select('id', 'name', 'slug')->get(),
            'serverTypes' => array_column(ServerType::cases(), 'value'),
            'serverStatuses' => array_column(ServerStatus::cases(), 'value'),
            'healthStatuses' => array_column(ServerHealthStatus::cases(), 'value'),
        ]);
    }

    /**
     * Show server creation form.
     */
    public function create(): Response
    {
        $this->authorize('create', Server::class);

        return Inertia::render('Admin/Servers/Create', [
            'serverGroups' => ServerGroup::select('id', 'name', 'location')->get(),
            'serverTypes' => array_column(ServerType::cases(), 'value'),
            'environments' => array_column(ServerEnvironment::cases(), 'value'),
            'authTypes' => array_column(ServerAuthType::cases(), 'value'),
        ]);
    }

    /**
     * Store newly registered server and dispatch async verification.
     */
    public function store(StoreServerRequest $request): RedirectResponse
    {
        $server = $this->serverService->registerServer($request->validated());

        // Dispatch async verification job
        VerifyServerJob::dispatch($server);

        return redirect()->route('admin.servers.show', $server->id)
            ->with('success', "Server '{$server->name}' registered successfully. Verification job queued.");
    }

    /**
     * Display server details with services, metrics, and event audit trail.
     */
    public function show(Server $server): Response
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

        return Inertia::render('Admin/Servers/Show', [
            'server' => $server,
            'allowedServices' => RemoteOperation::ALLOWED_SERVICES,
        ]);
    }

    /**
     * Show server edit form.
     */
    public function edit(Server $server): Response
    {
        $this->authorize('update', $server);

        return Inertia::render('Admin/Servers/Edit', [
            'server' => $server,
            'serverGroups' => ServerGroup::select('id', 'name', 'location')->get(),
            'serverTypes' => array_column(ServerType::cases(), 'value'),
            'environments' => array_column(ServerEnvironment::cases(), 'value'),
            'authTypes' => array_column(ServerAuthType::cases(), 'value'),
        ]);
    }

    /**
     * Update server metadata.
     */
    public function update(UpdateServerRequest $request, Server $server): RedirectResponse
    {
        $this->serverService->updateServer($server, $request->validated());

        return redirect()->route('admin.servers.show', $server->id)
            ->with('success', "Server '{$server->name}' updated successfully.");
    }

    /**
     * Delete or decommission a server node with dependency safeguards.
     */
    public function destroy(Server $server): RedirectResponse
    {
        $this->authorize('delete', $server);

        if ($server->is_master) {
            return back()->with('error', 'Cannot delete the cluster Master control plane node.');
        }

        $activeSubscriptionsCount = $server->subscriptions()->count();
        if ($activeSubscriptionsCount > 0) {
            return back()->with('error', "Cannot delete server: There are {$activeSubscriptionsCount} active hosting account(s) hosted on this node.");
        }

        $this->serverService->deleteServer($server);

        return redirect()->route('admin.servers.index')
            ->with('success', "Server '{$server->name}' decommissioned successfully.");
    }

    /**
     * Asynchronously trigger connectivity verification.
     */
    public function verify(Server $server): RedirectResponse
    {
        $this->authorize('verify', $server);

        VerifyServerJob::dispatch($server);

        return back()->with('success', "Verification initiated for server '{$server->name}'. Results will update shortly.");
    }

    /**
     * Asynchronously trigger hardware & runtime discovery.
     */
    public function discover(Server $server): RedirectResponse
    {
        $this->authorize('discover', $server);

        DiscoverServerJob::dispatch($server);

        return back()->with('success', "Hardware and service discovery queued for '{$server->name}'.");
    }

    /**
     * Asynchronously trigger full cluster synchronization.
     */
    public function sync(Server $server): RedirectResponse
    {
        $this->authorize('sync', $server);

        SyncServerJob::dispatch($server);

        return back()->with('success', "Complete node synchronization queued for '{$server->name}'.");
    }

    /**
     * Toggle Maintenance Mode.
     */
    public function maintenance(ToggleMaintenanceRequest $request, Server $server): RedirectResponse
    {
        $this->authorize('maintenance', $server);

        $enabled = $request->boolean('enabled');
        $reason = $request->input('reason');

        $this->serverService->setMaintenanceMode($server, $enabled, $reason);

        $stateText = $enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Maintenance mode {$stateText} for server '{$server->name}'.");
    }

    /**
     * Control allowlisted systemd services (restart, start, stop, reload).
     */
    public function manageService(
        ManageServerServiceRequest $request,
        Server $server,
        ServerServiceManager $serviceManager
    ): RedirectResponse {
        $this->authorize('manageServices', $server);

        $service = $request->input('service');
        $action = $request->input('action');

        $serviceManager->manageService($server, $service, $action);

        return back()->with('success', "Service action '{$action}' executed successfully on '{$service}'.");
    }

    /**
     * Explicitly approve discovered SSH host key fingerprint.
     */
    public function approveHostKey(ApproveHostKeyRequest $request, Server $server): RedirectResponse
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
            "SSH Host key fingerprint approved by administrator. Fingerprint: {$fingerprint}",
            ServerEventSeverity::INFO,
            ['old_fingerprint' => $oldFingerprint, 'new_fingerprint' => $fingerprint]
        );

        return back()->with('success', "SSH Host key fingerprint approved and locked for '{$server->name}'.");
    }

    /**
     * Rotate SSH credentials.
     */
    public function rotateCredential(
        RotateCredentialRequest $request,
        Server $server,
        ServerCredentialService $credentialService
    ): RedirectResponse {
        $this->authorize('manageCredentials', $server);

        $type = ServerCredentialType::from($request->input('credential_type'));
        $secret = $request->input('secret');
        $username = $request->input('username') ?: $server->ssh_user;
        $name = $request->input('name') ?: 'Rotated Credential';

        $credentialService->storeCredential($server, $type, $name, $username, $secret);

        if ($type === ServerCredentialType::SSH_PASSWORD) {
            $server->update(['encrypted_ssh_password' => $secret]);
        } elseif ($type === ServerCredentialType::SSH_PRIVATE_KEY) {
            $server->update(['encrypted_ssh_key' => $secret]);
        }

        return back()->with('success', "SSH credential rotated successfully for '{$server->name}'.");
    }
}
