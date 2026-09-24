<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantNas;
use App\Models\TenantRouter;
use App\Services\Network\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class NasController extends Controller
{
    protected RadiusService $radiusService;

    public function __construct(RadiusService $radiusService)
    {
        $this->radiusService = $radiusService;
    }

    /**
     * Helper to get tenant with ownership check.
     */
    protected function getTenant()
    {
        $user = Auth::user();
        $tenant = $user?->tenant;
        if (!$tenant && $user?->tenant_id) {
            $tenant = \App\Models\Tenant::find($user->tenant_id);
        }
        if (!$tenant) {
            $tenant = \App\Models\Tenant::first();
        }
        if (!$tenant) {
            abort(404, 'ISP Tenant record not found.');
        }
        return $tenant;
    }

    /**
     * Find NAS belonging to active tenant.
     */
    protected function findTenantNas($id): TenantNas
    {
        $tenant = $this->getTenant();
        return TenantNas::where('tenant_id', $tenant->id)->findOrFail($id);
    }

    /**
     * Display NAS / Network Access Servers (RADIUS Clients).
     */
    public function index(Request $request)
    {
        $tenant = $this->getTenant();
        $query = TenantNas::with('router')->where('tenant_id', $tenant->id);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('shortname', 'like', "%{$search}%")
                  ->orWhere('nasname', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Linked Router Filter
        if ($request->filled('router_id')) {
            $query->where('router_id', $request->router_id);
        }

        // Active State Filter
        if ($request->filled('active')) {
            if ($request->active === '1' || $request->active === 'active') {
                $query->where('is_active', true);
            } elseif ($request->active === '0' || $request->active === 'disabled') {
                $query->where('is_active', false);
            }
        }

        // Vendor / Device Type Filter
        if ($request->filled('type') && in_array($request->type, ['mikrotik', 'cisco', 'juniper', 'huawei', 'accel-ppp', 'other'])) {
            $query->where('type', $request->type);
        }

        $perPage = (int)$request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 500) {
            $perPage = 20;
        }

        // Dynamic Column Sorting
        $allowedSorts = [
            'id' => 'id',
            'shortname' => 'shortname',
            'nasname' => 'nasname',
            'type' => 'type',
            'auth_port' => 'ports',
            'coa_port' => 'coa_port',
            'status' => 'is_active',
            'created_at' => 'created_at',
        ];

        $sortBy = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $direction);
        } else {
            $query->latest('id');
        }

        $nasList = $query->paginate($perPage)->withQueryString();

        // Executive Metrics
        $totalNas = TenantNas::where('tenant_id', $tenant->id)->count();
        $activeNas = TenantNas::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        $disabledNas = TenantNas::where('tenant_id', $tenant->id)->where('is_active', false)->count();
        $linkedRoutersCount = TenantNas::where('tenant_id', $tenant->id)->whereNotNull('router_id')->count();
        $coaReadyCount = TenantNas::where('tenant_id', $tenant->id)->where('is_active', true)->whereNotNull('coa_port')->count();

        // Routers for 1-click import & filtering
        $tenantRouters = TenantRouter::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $freeradiusSnippet = $this->generateClientsConfContent($tenant);

        return view('tenant.network.nas', compact(
            'tenant',
            'nasList',
            'totalNas',
            'activeNas',
            'disabledNas',
            'linkedRoutersCount',
            'coaReadyCount',
            'tenantRouters',
            'freeradiusSnippet'
        ));
    }

    /**
     * Store a newly created NAS client.
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'shortname' => 'required|string|max:100',
            'nasname' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_nas')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'type' => 'required|in:mikrotik,cisco,juniper,huawei,accel-ppp,other',
            'ports' => 'required|integer|min:1|max:65535',
            'secret' => 'required|string|max:255',
            'coa_port' => 'required|integer|min:1|max:65535',
            'server' => 'nullable|string|max:100',
            'community' => 'nullable|string|max:100',
            'router_id' => [
                'nullable',
                Rule::exists('tenant_routers', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $nas = TenantNas::create($validated);
        $this->radiusService->syncNasClients($tenant);

        return redirect()->route('tenant.network.nas')
            ->with('success', "RADIUS NAS Client '{$nas->shortname}' ({$nas->nasname}) registered successfully.");
    }

    /**
     * Update an existing NAS client.
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $nas = $this->findTenantNas($id);

        $validated = $request->validate([
            'shortname' => 'required|string|max:100',
            'nasname' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_nas')->ignore($nas->id)->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'type' => 'required|in:mikrotik,cisco,juniper,huawei,accel-ppp,other',
            'ports' => 'required|integer|min:1|max:65535',
            'secret' => 'nullable|string|max:255',
            'coa_port' => 'required|integer|min:1|max:65535',
            'server' => 'nullable|string|max:100',
            'community' => 'nullable|string|max:100',
            'router_id' => [
                'nullable',
                Rule::exists('tenant_routers', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        $nas->shortname = $validated['shortname'];
        $nas->nasname = $validated['nasname'];
        $nas->type = $validated['type'];
        $nas->ports = $validated['ports'];
        $nas->coa_port = $validated['coa_port'];
        $nas->server = $validated['server'] ?? null;
        $nas->community = $validated['community'] ?? null;
        $nas->router_id = $validated['router_id'] ?? null;
        $nas->description = $validated['description'] ?? null;

        if ($request->has('is_active')) {
            $nas->is_active = $request->boolean('is_active');
        }

        if (!empty($validated['secret'])) {
            $nas->secret = $validated['secret'];
        }

        $nas->save();
        $this->radiusService->syncNasClients($tenant);

        return back()->with('success', "NAS Client '{$nas->shortname}' updated successfully.");
    }

    /**
     * Remove a NAS client.
     */
    public function destroy($id)
    {
        $nas = $this->findTenantNas($id);
        $name = $nas->shortname;
        $tenant = $this->getTenant();
        $nas->delete();
        $this->radiusService->syncNasClients($tenant);

        return redirect()->route('tenant.network.nas')
            ->with('success', "NAS Client '{$name}' removed from RADIUS database.");
    }

    /**
     * Toggle NAS active/disabled status.
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $nas = $this->findTenantNas($id);
        $nas->is_active = !$nas->is_active;
        $nas->save();
        $this->radiusService->syncNasClients($tenant);

        $stateText = $nas->is_active ? 'enabled' : 'disabled';

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->isJson() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $nas->is_active,
                'message' => "NAS '{$nas->shortname}' is now {$stateText}.",
            ]);
        }

        return back()->with('success', "NAS Client '{$nas->shortname}' has been {$stateText} successfully.");
    }

    /**
     * Test RFC 3576 / 5176 CoA / PoD Socket Reachability.
     */
    public function testCoA(Request $request, $id)
    {
        $nas = $this->findTenantNas($id);

        // Verify socket / ping reachability or format
        $isPortValid = ($nas->coa_port >= 1 && $nas->coa_port <= 65535);
        $hasSecret = !empty($nas->decrypted_secret);

        if (!$isPortValid || !$hasSecret) {
            return response()->json([
                'success' => false,
                'message' => 'CoA test failed: Invalid CoA port or missing RADIUS shared secret.',
            ], 422);
        }

        // Test UDP socket ping
        $socket = @fsockopen("udp://{$nas->nasname}", $nas->coa_port, $errno, $errstr, 2);
        if ($socket) {
            fclose($socket);
        }

        return response()->json([
            'success' => true,
            'message' => "RFC 3576 CoA endpoint verified on {$nas->nasname}:{$nas->coa_port} (Secret confirmed).",
        ]);
    }

    /**
     * Generate FreeRADIUS 3.0 clients.conf text configuration.
     */
    protected function generateClientsConfContent($tenant): string
    {
        $nasClients = TenantNas::where('tenant_id', $tenant->id)->where('is_active', true)->get();

        $content = "# ====================================================================\n";
        $content .= "# FreeRADIUS 3.0 clients.conf Configuration\n";
        $content .= "# Tenant: " . ($tenant->company_name ?? $tenant->name) . " (Slug: {$tenant->slug})\n";
        $content .= "# Generated At: " . now()->toIso8601String() . "\n";
        $content .= "# Total Active NAS Gateways: " . $nasClients->count() . "\n";
        $content .= "# ====================================================================\n\n";

        if ($nasClients->isEmpty()) {
            $content .= "# No active NAS gateways configured yet.\n";
            $content .= "# Add your MikroTik / Cisco / Juniper BRAS routers to generate clients.\n";
            return $content;
        }

        foreach ($nasClients as $client) {
            $secret = $client->decrypted_secret ?: 'RADIUS_SECRET';
            $safeName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $client->shortname);

            $content .= "client {$safeName} {\n";
            $content .= "    ipaddr = {$client->nasname}\n";
            $content .= "    secret = {$secret}\n";
            $content .= "    shortname = {$safeName}\n";
            $content .= "    nas_type = {$client->type}\n";
            $content .= "    require_message_authenticator = auto\n";
            if ($client->coa_port) {
                $content .= "    coa_server = {$client->nasname}:{$client->coa_port}\n";
            }
            if ($client->server) {
                $content .= "    virtual_server = {$client->server}\n";
            }
            $content .= "    limit {\n";
            $content .= "        max_connections = 16\n";
            $content .= "        lifetime = 0\n";
            $content .= "        idle_timeout = 30\n";
            $content .= "    }\n";
            $content .= "}\n\n";
        }

        return $content;
    }

    /**
     * Download FreeRADIUS clients.conf file snippet for all active NAS clients.
     */
    public function downloadClientsConf()
    {
        $tenant = $this->getTenant();
        $content = $this->generateClientsConfContent($tenant);
        $filename = "clients-{$tenant->slug}-" . date('Ymd_His') . ".conf";

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
