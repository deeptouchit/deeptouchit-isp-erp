<?php

namespace App\Http\Controllers\Tenant\Network;

use App\Http\Controllers\Controller;
use App\Models\TenantInternetPackage;
use App\Models\TenantIpPool;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InternetPackageController extends Controller
{
    protected MikrotikApiService $mikrotikApiService;
    protected RadiusService $radiusService;

    public function __construct(MikrotikApiService $mikrotikApiService, RadiusService $radiusService)
    {
        $this->mikrotikApiService = $mikrotikApiService;
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
     * Display a listing of Internet Packages & Bandwidth Plans.
     */
    public function index(Request $request)
    {
        $tenant = $this->getTenant();

        $routers = TenantRouter::where('tenant_id', $tenant->id)->get(['id', 'name', 'ip_address', 'is_active']);
        $ipPools = TenantIpPool::where('tenant_id', $tenant->id)->where('status', '!=', 'disabled')->get(['id', 'tenant_id', 'router_id', 'name', 'range_start', 'range_end', 'pool_type']);

        $query = TenantInternetPackage::where('tenant_id', $tenant->id)->with(['router', 'ipPool']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('local_address', 'like', "%{$search}%")
                  ->orWhere('remote_address', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%")
                  ->orWhere('mikrotik_profile', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Router Filter
        if ($routerId = $request->input('router_id')) {
            $query->where('router_id', $routerId);
        }

        // Service Type Filter (pppoe, hotspot, static, all)
        if ($serviceType = $request->input('service_type')) {
            $query->where('service_type', $serviceType);
        }

        // IP Pool Filter
        if ($poolId = $request->input('ip_pool_id')) {
            $query->where('ip_pool_id', $poolId);
        }

        // Active State Filter
        if ($request->has('active') && $request->input('active') !== '') {
            $activeVal = $request->input('active');
            if ($activeVal === '1' || $activeVal === 'active') {
                $query->where('is_active', true);
            } elseif ($activeVal === '0' || $activeVal === 'disabled') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->input('per_page', 20);
        if ($perPage <= 0 || $perPage > 200) {
            $perPage = 20;
        }

        // Dynamic Column Sorting
        $allowedSorts = [
            'id' => 'id',
            'package' => 'package_name',
            'price' => 'price',
            'name' => 'name',
            'local_address' => 'local_address',
            'remote_address' => 'remote_address',
            'only_one' => 'only_one',
            'status' => 'is_active',
            'created_at' => 'created_at',
        ];

        $sortBy = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $direction);
        } else {
            $query->orderBy('id', 'asc');
        }

        $packages = $query->paginate($perPage)->withQueryString();

        // 6-Card Summary Metrics
        $metricBase = TenantInternetPackage::where('tenant_id', $tenant->id);
        $totalPackages = (clone $metricBase)->count();
        $activePackages = (clone $metricBase)->where('is_active', true)->count();
        $pppoePackages = (clone $metricBase)->where('service_type', 'pppoe')->count();
        $hotspotPackages = (clone $metricBase)->where('service_type', 'hotspot')->count();
        $avgPrice = (clone $metricBase)->where('is_active', true)->avg('price') ?? 0;
        $syncedPackages = (clone $metricBase)->where('is_sync_mikrotik', true)->count();

        return view('tenant.network.packages', compact(
            'tenant',
            'packages',
            'routers',
            'ipPools',
            'totalPackages',
            'activePackages',
            'pppoePackages',
            'hotspotPackages',
            'avgPrice',
            'syncedPackages'
        ));
    }

    /**
     * Store a newly created Internet Package.
     */
    public function store(Request $request)
    {
        $tenant = $this->getTenant();

        $validated = $request->validate([
            'package_name' => 'required|string|max:150',
            'name' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:50',
            'service_type' => 'required|in:pppoe,hotspot,static',
            'download_speed' => 'nullable|string|max:50',
            'upload_speed' => 'nullable|string|max:50',
            'rate_download_mbps' => 'nullable|numeric|min:0',
            'rate_upload_mbps' => 'nullable|numeric|min:0',
            'facebook_speed' => 'nullable|string|max:50',
            'youtube_speed' => 'nullable|string|max:50',
            'bdix_speed' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|string|max:50',
            'validity_unit' => 'nullable|string|max:20',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_pool_id' => 'nullable|exists:tenant_ip_pools,id',
            'local_address' => 'nullable|string|max:100',
            'remote_address' => 'nullable|string|max:100',
            'only_one' => 'nullable|in:default,yes,no',
            'mikrotik_profile' => 'nullable|string|max:150',
            'address_list' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'comment' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'sync_now' => 'nullable|boolean',
        ]);

        $packageName = trim($validated['package_name']);
        $profileName = !empty($validated['name'])
            ? trim($validated['name'])
            : (!empty($validated['mikrotik_profile'])
                ? trim($validated['mikrotik_profile'])
                : preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($packageName)));

        $downloadSpeed = $validated['download_speed'] ?? (isset($validated['rate_download_mbps']) ? (string)$validated['rate_download_mbps'] : '10');
        $uploadSpeed = $validated['upload_speed'] ?? (isset($validated['rate_upload_mbps']) ? (string)$validated['rate_upload_mbps'] : '10');

        $remoteAddress = $validated['remote_address'] ?? null;
        if (empty($remoteAddress) && !empty($validated['ip_pool_id'])) {
            $pool = TenantIpPool::find($validated['ip_pool_id']);
            if ($pool) {
                $remoteAddress = $pool->name;
            }
        }

        $code = !empty($validated['code']) ? trim($validated['code']) : ('PKG-' . strtoupper(substr(md5($packageName . microtime()), 0, 4)));
        $comment = $validated['comment'] ?? null;
        $description = $validated['description'] ?? null;

        $package = TenantInternetPackage::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'service_type' => $validated['service_type'],
            'router_id' => $validated['router_id'] ?? null,
            'ip_pool_id' => $validated['ip_pool_id'] ?? null,
            'name' => $profileName,
            'local_address' => $validated['local_address'] ?? null,
            'remote_address' => $remoteAddress,
            'only_one' => $validated['only_one'] ?? 'default',
            'comment' => $comment,
            'package_name' => $packageName,
            'price' => $validated['price'],
            'validity_days' => $validated['validity_days'],
            'validity_unit' => $validated['validity_unit'] ?? 'days',
            'upload_speed' => (string) $uploadSpeed,
            'download_speed' => (string) $downloadSpeed,
            'facebook_speed' => (string) ($validated['facebook_speed'] ?? '0'),
            'youtube_speed' => (string) ($validated['youtube_speed'] ?? '0'),
            'bdix_speed' => (string) ($validated['bdix_speed'] ?? '0'),
            'mikrotik_profile' => $profileName,
            'address_list' => $validated['address_list'] ?? null,
            'is_sync_mikrotik' => false,
            'is_active' => $request->has('is_active'),
            'description' => $description,
        ]);

        // Automatically synchronize to FreeRADIUS group attributes
        $this->radiusService->syncPackageGroup($package);

        $syncNote = "";
        if ($package->router_id && $request->boolean('sync_now', true)) {
            $syncRes = $this->pushPackageToRouter($package);
            if (!empty($syncRes['success'])) {
                $syncNote = " and synchronized with MikroTik ({$package->router?->name})";
            } else {
                $syncNote = " (MikroTik sync note: " . ($syncRes['message'] ?? 'Could not reach router') . ")";
            }
        }

        return redirect()->route('tenant.network.packages')
            ->with('success', "Internet Package '{$package->package_name}' ({$package->name}) created{$syncNote}.");
    }

    /**
     * Update an existing Internet Package.
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->getTenant();
        $package = TenantInternetPackage::where('tenant_id', $tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'package_name' => 'required|string|max:150',
            'name' => 'nullable|string|max:150',
            'code' => 'nullable|string|max:50',
            'service_type' => 'required|in:pppoe,hotspot,static',
            'download_speed' => 'nullable|string|max:50',
            'upload_speed' => 'nullable|string|max:50',
            'rate_download_mbps' => 'nullable|numeric|min:0',
            'rate_upload_mbps' => 'nullable|numeric|min:0',
            'facebook_speed' => 'nullable|string|max:50',
            'youtube_speed' => 'nullable|string|max:50',
            'bdix_speed' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|string|max:50',
            'validity_unit' => 'nullable|string|max:20',
            'router_id' => 'nullable|exists:tenant_routers,id',
            'ip_pool_id' => 'nullable|exists:tenant_ip_pools,id',
            'local_address' => 'nullable|string|max:100',
            'remote_address' => 'nullable|string|max:100',
            'only_one' => 'nullable|in:default,yes,no',
            'mikrotik_profile' => 'nullable|string|max:150',
            'address_list' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'comment' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:1000',
            'sync_now' => 'nullable|boolean',
        ]);

        $packageName = trim($validated['package_name']);
        $profileName = !empty($validated['name'])
            ? trim($validated['name'])
            : (!empty($validated['mikrotik_profile'])
                ? trim($validated['mikrotik_profile'])
                : ($package->name ?: $package->mikrotik_profile));

        $downloadSpeed = $validated['download_speed'] ?? (isset($validated['rate_download_mbps']) ? (string)$validated['rate_download_mbps'] : $package->download_speed);
        $uploadSpeed = $validated['upload_speed'] ?? (isset($validated['rate_upload_mbps']) ? (string)$validated['rate_upload_mbps'] : $package->upload_speed);

        $remoteAddress = $validated['remote_address'] ?? null;
        if (empty($remoteAddress) && !empty($validated['ip_pool_id'])) {
            $pool = TenantIpPool::find($validated['ip_pool_id']);
            if ($pool) {
                $remoteAddress = $pool->name;
            }
        }

        $comment = array_key_exists('comment', $validated) ? $validated['comment'] : $package->comment;
        $description = array_key_exists('description', $validated) ? $validated['description'] : $package->description;

        $package->update([
            'code' => $validated['code'] ?? $package->code,
            'service_type' => $validated['service_type'],
            'router_id' => $validated['router_id'] ?? null,
            'ip_pool_id' => $validated['ip_pool_id'] ?? null,
            'name' => $profileName,
            'local_address' => $validated['local_address'] ?? null,
            'remote_address' => $remoteAddress ?? $package->remote_address,
            'only_one' => $validated['only_one'] ?? 'default',
            'comment' => $comment,
            'package_name' => $packageName,
            'price' => $validated['price'],
            'validity_days' => $validated['validity_days'],
            'validity_unit' => $validated['validity_unit'] ?? 'days',
            'upload_speed' => (string) $uploadSpeed,
            'download_speed' => (string) $downloadSpeed,
            'facebook_speed' => (string) ($validated['facebook_speed'] ?? $package->facebook_speed ?? '0'),
            'youtube_speed' => (string) ($validated['youtube_speed'] ?? $package->youtube_speed ?? '0'),
            'bdix_speed' => (string) ($validated['bdix_speed'] ?? $package->bdix_speed ?? '0'),
            'mikrotik_profile' => $profileName,
            'address_list' => $validated['address_list'] ?? null,
            'is_active' => $request->has('is_active'),
            'description' => $description,
        ]);

        // Automatically synchronize updated attributes to FreeRADIUS group
        $this->radiusService->syncPackageGroup($package);

        $syncNote = "";
        if ($package->router_id && ($request->boolean('sync_now') || $package->is_sync_mikrotik || $request->has('sync_now'))) {
            $syncRes = $this->pushPackageToRouter($package);
            if (!empty($syncRes['success'])) {
                $syncNote = " and updated on MikroTik ({$package->router?->name})";
            } else {
                $syncNote = " (MikroTik sync note: " . ($syncRes['message'] ?? 'Could not reach router') . ")";
            }
        }

        return redirect()->route('tenant.network.packages')
            ->with('success', "Internet Package '{$package->package_name}' ({$package->name}) updated{$syncNote}.");
    }

    /**
     * Delete an Internet Package.
     */
    public function destroy($id)
    {
        $tenant = $this->getTenant();

        $package = TenantInternetPackage::where('tenant_id', $tenant->id)->with('router')->findOrFail($id);
        $name = $package->name;
        $profileName = $package->mikrotik_profile ?: preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($package->name));

        // If linked to MikroTik router, remove profile from MikroTik
        if ($package->router) {
            try {
                if ($package->service_type === 'hotspot') {
                    $this->mikrotikApiService->removeHotspotProfile($package->router, $profileName);
                } else {
                    $this->mikrotikApiService->removePppProfile($package->router, $profileName);
                }
            } catch (\Exception $e) {
                Log::warning("Could not delete profile '{$profileName}' from MikroTik: " . $e->getMessage());
            }
        }

        // Clean up FreeRADIUS group reply & check attributes
        $this->radiusService->deletePackageGroup($package);

        $package->delete();

        return redirect()->route('tenant.network.packages')
            ->with('success', "Internet Package '{$name}' removed from system, RADIUS, and MikroTik.");
    }

    /**
     * Toggle Active status of a package.
     */
    public function toggleStatus(Request $request, $id)
    {
        $tenant = $this->getTenant();

        $package = TenantInternetPackage::where('tenant_id', $tenant->id)->findOrFail($id);
        $package->is_active = !$package->is_active;
        $package->save();

        // Update RADIUS group
        $this->radiusService->syncPackageGroup($package);

        $stateText = $package->is_active ? 'enabled' : 'disabled';

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json([
                'success' => true,
                'is_active' => $package->is_active,
                'message' => "Package '{$package->name}' is now {$stateText}.",
            ]);
        }

        return redirect()->back()->with('success', "Package '{$package->name}' has been {$stateText} successfully.");
    }

    /**
     * Sync package profile to linked MikroTik router.
     */
    public function syncMikrotik(Request $request, $id)
    {
        $tenant = $this->getTenant();

        $package = TenantInternetPackage::where('tenant_id', $tenant->id)->with(['router', 'ipPool'])->findOrFail($id);

        if (!$package->router_id || !$package->router) {
            if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot sync: Package '{$package->name}' has no assigned MikroTik Router.",
                ], 422);
            }
            return redirect()->back()->with('error', "Cannot sync: Package '{$package->name}' has no assigned MikroTik Router.");
        }

        $res = $this->pushPackageToRouter($package);

        if ($request->wantsJson() || $request->ajax() || str_contains($request->header('Accept', ''), 'json')) {
            return response()->json($res);
        }

        if ($res['success']) {
            return redirect()->route('tenant.network.packages')
                ->with('success', $res['message']);
        }

        return redirect()->route('tenant.network.packages')
            ->with('error', $res['message']);
    }

    /**
     * Import existing PPP & Hotspot profiles from a selected MikroTik Router.
     */
    public function importFromMikrotik(Request $request)
    {
        $tenant = $this->getTenant();

        $request->validate([
            'router_id' => 'required|exists:tenant_routers,id',
            'import_type' => 'required|in:pppoe,hotspot,both',
        ]);

        $router = TenantRouter::where('tenant_id', $tenant->id)->findOrFail($request->input('router_id'));
        $importType = $request->input('import_type');

        try {
            $importedCount = 0;
            $updatedCount = 0;

            // 1. Import PPPoE Profiles (/ppp/profile)
            if ($importType === 'pppoe' || $importType === 'both') {
                $pullRes = $this->mikrotikApiService->pullPppProfiles($router);
                if (!$pullRes['success']) {
                    return redirect()->route('tenant.network.packages')
                        ->with('error', "Failed to connect to MikroTik '{$router->name}': " . ($pullRes['message'] ?? 'Connection error'));
                }

                foreach ($pullRes['profiles'] as $p) {
                    $profileName = $p['name'] ?? null;
                    if (!$profileName || in_array(strtolower($profileName), ['default', 'default-encryption'])) {
                        continue; // Skip MikroTik factory defaults
                    }

                    $rateLimit = $p['rate_limit'] ?: '10M/10M';
                    $rates = $this->parseRateLimitString($rateLimit);

                    $remotePool = $p['remote_address'] ?? null;
                    $poolId = null;
                    if ($remotePool) {
                        $pool = TenantIpPool::where('tenant_id', $tenant->id)->where('name', $remotePool)->first();
                        if ($pool) {
                            $poolId = $pool->id;
                        }
                    }

                    $existing = TenantInternetPackage::where('tenant_id', $tenant->id)
                        ->where('mikrotik_profile', $profileName)
                        ->first();

                    $data = [
                        'router_id' => $router->id,
                        'rate_download' => $rates['download_kbps'],
                        'rate_upload' => $rates['upload_kbps'],
                        'rate_limit_formatted' => $rateLimit,
                        'local_address' => $p['local_address'] ?? null,
                        'ip_pool_id' => $poolId,
                        'dns_servers' => $p['dns_server'] ?? null,
                        'address_list' => $p['address_list'] ?? null,
                        'only_one' => $p['only_one'] ?? 'default',
                        'parent_queue' => $p['parent_queue'] ?? null,
                        'queue_type' => $p['queue_type'] ?? null,
                        'session_timeout' => $p['session_timeout'] ?? null,
                        'idle_timeout' => $p['idle_timeout'] ?? null,
                        'use_ipv6' => $p['use_ipv6'] ?? 'default',
                        'use_encryption' => $p['use_encryption'] ?? 'default',
                        'on_up_script' => $p['on_up'] ?? null,
                        'on_down_script' => $p['on_down'] ?? null,
                        'is_sync_mikrotik' => true,
                    ];

                    if ($existing) {
                        $data['ip_pool_id'] = $poolId ?? $existing->ip_pool_id;
                        $existing->update($data);
                        $updatedCount++;
                    } else {
                        TenantInternetPackage::create(array_merge($data, [
                            'tenant_id' => $tenant->id,
                            'name' => ucwords(str_replace(['_', '-'], ' ', $profileName)),
                            'code' => 'PKG-' . strtoupper(substr(md5($profileName), 0, 4)),
                            'service_type' => 'pppoe',
                            'price' => 500.00,
                            'validity_days' => 30,
                            'validity_unit' => 'days',
                            'mikrotik_profile' => $profileName,
                            'is_active' => true,
                            'description' => "Imported from MikroTik router '{$router->name}' (/ppp/profile)",
                        ]));
                        $importedCount++;
                    }
                }
            }

            // 2. Import Hotspot Profiles (/ip/hotspot/user/profile)
            if ($importType === 'hotspot' || $importType === 'both') {
                $hotspotRes = $this->mikrotikApiService->pullHotspotProfiles($router);
                if ($hotspotRes['success']) {
                    foreach ($hotspotRes['profiles'] as $hp) {
                        $profileName = $hp['name'] ?? null;
                        if (!$profileName || strtolower($profileName) === 'default') {
                            continue;
                        }

                        $rateLimit = $hp['rate_limit'] ?: '5M/5M';
                        $rates = $this->parseRateLimitString($rateLimit);

                        $poolName = $hp['address_pool'] ?? null;
                        $poolId = null;
                        if ($poolName) {
                            $pool = TenantIpPool::where('tenant_id', $tenant->id)->where('name', $poolName)->first();
                            if ($pool) {
                                $poolId = $pool->id;
                            }
                        }

                        $existing = TenantInternetPackage::where('tenant_id', $tenant->id)
                            ->where('mikrotik_profile', $profileName)
                            ->first();

                        $data = [
                            'router_id' => $router->id,
                            'rate_download' => $rates['download_kbps'],
                            'rate_upload' => $rates['upload_kbps'],
                            'rate_limit_formatted' => $rateLimit,
                            'ip_pool_id' => $poolId,
                            'shared_users' => (int)($hp['shared_users'] ?? 1),
                            'address_list' => $hp['address_list'] ?? null,
                            'parent_queue' => $hp['parent_queue'] ?? null,
                            'queue_type' => $hp['queue_type'] ?? null,
                            'session_timeout' => $hp['session_timeout'] ?? null,
                            'idle_timeout' => $hp['idle_timeout'] ?? null,
                            'on_up_script' => $hp['on_login'] ?? null,
                            'on_down_script' => $hp['on_logout'] ?? null,
                            'is_sync_mikrotik' => true,
                        ];

                        if ($existing) {
                            $data['ip_pool_id'] = $poolId ?? $existing->ip_pool_id;
                            $existing->update($data);
                            $updatedCount++;
                        } else {
                            TenantInternetPackage::create(array_merge($data, [
                                'tenant_id' => $tenant->id,
                                'name' => ucwords(str_replace(['_', '-'], ' ', $profileName)),
                                'code' => 'HOTSPOT-' . strtoupper(substr(md5($profileName), 0, 4)),
                                'service_type' => 'hotspot',
                                'price' => 100.00,
                                'validity_days' => 7,
                                'validity_unit' => 'days',
                                'mikrotik_profile' => $profileName,
                                'is_active' => true,
                                'description' => "Imported from MikroTik router '{$router->name}' (/ip/hotspot/user/profile)",
                            ]));
                            $importedCount++;
                        }
                    }
                }
            }

            return redirect()->route('tenant.network.packages')
                ->with('success', "Import Complete: {$importedCount} new packages created, {$updatedCount} profiles updated from '{$router->name}'.");

        } catch (\Exception $e) {
            Log::error("Failed to import packages from MikroTik Router #{$router->id}: " . $e->getMessage());
            return redirect()->route('tenant.network.packages')
                ->with('error', "Import failed from MikroTik: {$e->getMessage()}");
        }
    }

    /**
     * Internal helper to push a package profile to MikroTik Router via RouterOS API.
     */
    protected function pushPackageToRouter(TenantInternetPackage $package): array
    {
        $package->loadMissing(['router', 'ipPool']);
        if (!$package->router_id || !$package->router) {
            return ['success' => false, 'message' => "Cannot sync: Package '{$package->name}' has no assigned MikroTik Router."];
        }

        try {
            $router = $package->router;
            $profileName = $package->name ?: ($package->mikrotik_profile ?: preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($package->package_name ?: 'plan')));
            $remoteAddress = $package->remote_address ?: ($package->ipPool ? $package->ipPool->name : null);
            $comment = $package->comment ?: null;

            if ($package->service_type === 'hotspot') {
                $res = $this->mikrotikApiService->pushHotspotProfile(
                    $router,
                    $profileName,
                    null, // Keep rate-limit blank in MikroTik profile
                    $remoteAddress,
                    $package->address_list,
                    1,
                    $comment
                );
            } else {
                $res = $this->mikrotikApiService->pushPppProfile(
                    $router,
                    $profileName,
                    null, // Keep rate-limit blank in MikroTik profile
                    $package->local_address,
                    $remoteAddress,
                    null,
                    $comment,
                    $package->address_list,
                    $package->only_one ?: 'default'
                );
            }

            if (!empty($res['success'])) {
                $package->update([
                    'is_sync_mikrotik' => true,
                    'mikrotik_profile' => $profileName,
                ]);
            }

            return $res;

        } catch (\Exception $e) {
            Log::error("Failed to push package #{$package->id} to MikroTik: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "MikroTik Sync failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Helper to parse speed strings like '10M/10M' or '512k/512k 1M/1M 256k/256k 10/10'
     */
    protected function parseRateLimitString(string $rateLimit): array
    {
        $parts = explode(' ', trim($rateLimit));
        $mainRate = $parts[0] ?? '10M/10M';
        $rateParts = explode('/', $mainRate);

        $rx = $rateParts[0] ?? '10M'; // Upload (Rx on router)
        $tx = $rateParts[1] ?? '10M'; // Download (Tx on router)

        return [
            'upload_kbps' => TenantInternetPackage::parseSpeedToKbps($rx),
            'download_kbps' => TenantInternetPackage::parseSpeedToKbps($tx),
        ];
    }

    /**
     * Fetch IP Pools for a specific router (API for dynamic dropdown)
     */
    public function getRouterIpPools(Request $request, $routerId)
    {
        $tenant = $this->getTenant();

        $query = TenantIpPool::where('tenant_id', $tenant->id)
            ->where('status', '!=', 'disabled');

        if (!empty($routerId) && $routerId !== 'all') {
            $query->where(function($q) use ($routerId) {
                $q->where('router_id', $routerId)
                  ->orWhereNull('router_id');
            });
        }

        $pools = $query->get(['id', 'tenant_id', 'router_id', 'name', 'range_start', 'range_end', 'pool_type'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'router_id' => $p->router_id,
                    'name' => $p->name,
                    'type' => $p->pool_type,
                    'range' => $p->addresses_display ?? ($p->range_start ? $p->range_start . '-' . $p->range_end : '')
                ];
            });

        return response()->json([
            'success' => true,
            'router_id' => $routerId,
            'pools' => $pools
        ]);
    }
}
