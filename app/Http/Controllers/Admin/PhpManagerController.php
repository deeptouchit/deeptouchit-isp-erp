<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Website;
use App\Services\PHP\PhpCompatibilityService;
use App\Services\PHP\PhpExtensionService;
use App\Services\PHP\PhpFpmPoolService;
use App\Services\PHP\PhpLogService;
use App\Services\PHP\PhpManagerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PhpManagerController extends Controller
{
    protected PhpManagerService $phpManager;
    protected PhpCompatibilityService $compatibilityService;
    protected PhpFpmPoolService $poolService;
    protected PhpExtensionService $extensionService;
    protected PhpLogService $logService;

    public function __construct(
        PhpManagerService $phpManager,
        PhpCompatibilityService $compatibilityService,
        PhpFpmPoolService $poolService,
        PhpExtensionService $extensionService,
        PhpLogService $logService
    ) {
        $this->phpManager = $phpManager;
        $this->compatibilityService = $compatibilityService;
        $this->poolService = $poolService;
        $this->extensionService = $extensionService;
        $this->logService = $logService;
    }

    /**
     * Render the Master PHP Manager Dashboard.
     */
    public function index(Request $request): Response
    {
        $summary = $this->phpManager->getDashboardSummary();
        $discovered = $this->phpManager->discovery()->discoverInstalledVersions();

        // Selected PHP version for Configuration & Pools tabs
        $selectedVersion = $request->query('version');
        if (!$selectedVersion || !isset($discovered[$selectedVersion])) {
            $selectedVersion = $summary['default_php_version'] ?? array_key_first($discovered) ?? '8.2';
        }

        // Directives for the selected version
        $directives = isset($discovered[$selectedVersion])
            ? $this->phpManager->config()->getDirectives($selectedVersion)
            : [];

        // FPM Pools for the selected version
        $pools = isset($discovered[$selectedVersion])
            ? $this->poolService->getPools($selectedVersion)
            : [];

        // All Hosted Websites for per-site switching
        $websites = Website::with(['subscription.user', 'subscription.plan'])
            ->latest()
            ->get();

        // Audit & History Logs for PHP Operations
        $auditLogs = ActivityLog::with('user')
            ->where(function ($q) {
                $q->where('action', 'like', 'php_%')
                  ->orWhere('action', 'like', '%php%')
                  ->orWhere('action', 'like', '%opcache%');
            })
            ->latest()
            ->take(30)
            ->get();

        // Determine Active Tab based on Route
        $routeName = $request->route() ? $request->route()->getName() : '';
        $currentTab = 'versions';
        if (str_contains($routeName, 'configuration')) {
            $currentTab = 'config';
        } elseif (str_contains($routeName, 'per-site')) {
            $currentTab = 'sites';
        } elseif (str_contains($routeName, 'pools')) {
            $currentTab = 'pools';
        } elseif (str_contains($routeName, 'extensions')) {
            $currentTab = 'extensions';
        } elseif (str_contains($routeName, 'install-remove')) {
            $currentTab = 'install';
        } elseif (str_contains($routeName, 'logs')) {
            $currentTab = 'audit';
        } elseif ($tab = $request->query('tab')) {
            $currentTab = $tab;
        }

        return Inertia::render('Admin/PHP/Index', [
            'summary' => $summary,
            'discovered' => $discovered,
            'selectedVersion' => $selectedVersion,
            'currentTab' => $currentTab,
            'directives' => $directives,
            'pools' => $pools,
            'websites' => $websites,
            'auditLogs' => $auditLogs,
            'supportedDirectives' => $this->phpManager->config()->getSupportedDirectives(),
        ]);
    }

    /**
     * Start / Stop / Restart / Reload a PHP-FPM Service.
     */
    public function serviceAction(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'action' => 'required|string|in:start,stop,restart,reload',
        ]);

        $res = $this->phpManager->fpm()->manageService($validated['version'], $validated['action']);

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Update PHP Directives (php.ini) for a specific version.
     */
    public function updateConfiguration(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'settings' => 'required|array',
        ]);

        $res = $this->phpManager->config()->updateDirectives($validated['version'], $validated['settings'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Switch Website PHP Version (Nginx FastCGI socket mapping).
     */
    public function switchSiteVersion(Request $request)
    {
        $validated = $request->validate([
            'website_id' => 'required|exists:websites,id',
            'target_version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $website = Website::findOrFail($validated['website_id']);
        $res = $this->phpManager->switchWebsitePhpVersion($website, $validated['target_version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Check compatibility of a website with target PHP version (JSON response).
     */
    public function checkCompatibility(Request $request)
    {
        $validated = $request->validate([
            'website_id' => 'required|exists:websites,id',
            'target_version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $website = Website::findOrFail($validated['website_id']);
        $report = $this->compatibilityService->checkCompatibility($website, $validated['target_version']);

        return response()->json($report);
    }

    /**
     * Update FPM Pool Settings.
     */
    public function updatePool(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'pool_name' => 'required|string|max:64',
            'settings' => 'nullable|array',
            'pm' => 'nullable|string|in:dynamic,static,ondemand',
            'pm_max_children' => 'nullable|integer|min:2|max:1000',
            'pm_start_servers' => 'nullable|integer|min:1|max:1000',
            'pm_min_spare_servers' => 'nullable|integer|min:1|max:1000',
            'pm_max_spare_servers' => 'nullable|integer|min:1|max:1000',
            'pm_process_idle_timeout' => 'nullable|string|max:32',
            'pm_max_requests' => 'nullable|integer|min:0|max:50000',
            'request_terminate_timeout' => 'nullable|string|max:32',
            'memory_limit' => 'nullable|string|max:32',
        ]);

        $settings = $validated['settings'] ?? $validated;

        $res = $this->poolService->updatePool(
            $validated['version'],
            $validated['pool_name'],
            $settings,
            auth()->id()
        );

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Repair all detected configuration drift.
     */
    public function repairDrift(Request $request)
    {
        $driftReports = $this->phpManager->sync()->detectDrift();
        $repaired = 0;

        foreach ($driftReports as $d) {
            $site = Website::find($d['website_id']);
            if ($site) {
                $res = $this->phpManager->switchWebsitePhpVersion($site, $d['expected_version'], auth()->id());
                if ($res['success']) {
                    $repaired++;
                }
            }
        }

        return redirect()->back()->with('success', "Repaired {$repaired} drifted website configuration(s) successfully.");
    }

    /**
     * Flush OPcache for a specific PHP version.
     */
    public function flushOpcache(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->phpManager->flushOpcache($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Set Server Default PHP Version.
     */
    public function setDefaultVersion(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->phpManager->setDefaultPhpVersion($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Render the PHP Version Lifecycle & Package Manager (Install / Remove).
     */
    public function installRemove(Request $request, \App\Services\PHP\PhpPackageService $packageService): Response
    {
        $catalog = $packageService->getAvailableCatalog();
        $installedCount = count(array_filter($catalog, fn($c) => $c['is_installed']));
        $availableCount = count($catalog);
        $supportedCount = count(array_filter($catalog, fn($c) => in_array($c['status_type'], ['latest', 'active'])));

        $stats = [
            'installed_count' => $installedCount,
            'available_count' => $availableCount,
            'supported_count' => $supportedCount,
            'default_version' => \App\Models\SystemSetting::get('default_php_version', '8.5'),
        ];

        return Inertia::render('Admin/PHP/InstallRemove', [
            'catalog' => $catalog,
            'stats' => $stats,
        ]);
    }

    /**
     * Install a PHP version and core packages.
     */
    public function installVersion(Request $request, \App\Services\PHP\PhpPackageService $packageService)
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $packageService->installVersion($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Safely uninstall a PHP version.
     */
    public function uninstallVersion(Request $request, \App\Services\PHP\PhpPackageService $packageService)
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $packageService->uninstallVersion($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Render the PHP Extensions Management View.
     */
    public function extensions(Request $request): Response
    {
        $discovered = $this->phpManager->discovery()->discoverInstalledVersions();
        $installedVersions = array_keys($discovered);
        
        $selectedVersion = $request->query('version');
        if (!$selectedVersion || !in_array($selectedVersion, $installedVersions, true)) {
            $selectedVersion = \App\Models\SystemSetting::get('default_php_version', $installedVersions[0] ?? '8.5');
        }

        $catalog = $this->extensionService->getCatalog($selectedVersion);
        $stats = $this->extensionService->getStats($selectedVersion);

        // Group catalog by category
        $categories = [];
        foreach ($catalog as $ext) {
            $cat = $ext['category'] ?? 'General';
            $categories[$cat][] = $ext;
        }

        return Inertia::render('Admin/PHP/Extensions', [
            'catalog' => $catalog,
            'categories' => $categories,
            'stats' => $stats,
            'selectedVersion' => $selectedVersion,
            'installedVersions' => $installedVersions,
            'defaultVersion' => \App\Models\SystemSetting::get('default_php_version', '8.5'),
        ]);
    }

    /**
     * Toggle or install/remove a PHP extension.
     */
    public function toggleExtension(Request $request)
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'extension' => 'required|string|max:64',
            'action' => 'required|string|in:install,remove,enable,disable',
        ]);

        $res = $this->extensionService->toggleExtension(
            $validated['version'],
            $validated['extension'],
            $validated['action'],
            auth()->id()
        );

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Install the complete recommended hosting extension stack.
     */
    public function installRecommendedStack(Request $request)
    {
        @set_time_limit(300);

        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->extensionService->installRecommendedStack($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Render the PHP-FPM Pools Management View.
     */
    public function pools(Request $request): Response
    {
        $allPools = $this->poolService->getAllPools();
        $stats = $this->poolService->getPoolStats();
        $installedVersions = array_keys($this->phpManager->discovery()->discoverInstalledVersions());

        return Inertia::render('Admin/PHP/Pools', [
            'pools' => $allPools,
            'stats' => $stats,
            'installedVersions' => $installedVersions,
            'defaultVersion' => \App\Models\SystemSetting::get('default_php_version', '8.5'),
        ]);
    }

    /**
     * Create a new isolated FPM pool.
     */
    public function createPool(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'pool_name' => 'required|string|regex:/^[a-z0-9_-]+$/|max:32',
            'user' => 'nullable|string|max:64',
            'group' => 'nullable|string|max:64',
            'listen' => 'nullable|string|max:255',
            'pm' => 'required|string|in:dynamic,static,ondemand',
            'pm_max_children' => 'required|integer|min:2|max:1000',
            'pm_start_servers' => 'required|integer|min:1|max:1000',
            'pm_min_spare_servers' => 'required|integer|min:1|max:1000',
            'pm_max_spare_servers' => 'required|integer|min:1|max:1000',
            'pm_max_requests' => 'required|integer|min:0|max:50000',
            'memory_limit' => 'nullable|string|max:32',
        ]);

        $res = $this->poolService->createPool(
            $validated['version'],
            $validated['pool_name'],
            $validated,
            auth()->id()
        );

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Safely delete a custom FPM pool.
     */
    public function deletePool(string $version, string $poolName)
    {
        $res = $this->poolService->deletePool($version, $poolName, auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Restart/Reload the FPM daemon for a version.
     */
    public function restartPool(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->poolService->restartPool($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Render the PHP Configuration (php.ini) Management View.
     */
    public function configuration(Request $request): Response
    {
        $discovered = $this->phpManager->discovery()->discoverInstalledVersions();
        $installedVersions = array_keys($discovered);
        
        $selectedVersion = $request->query('version');
        if (!$selectedVersion || !in_array($selectedVersion, $installedVersions, true)) {
            $selectedVersion = \App\Models\SystemSetting::get('default_php_version', $installedVersions[0] ?? '8.5');
        }

        $directives = $this->phpManager->config()->getDirectives($selectedVersion);
        $rawIni = $this->phpManager->config()->getRawIni($selectedVersion);
        $supportedDirectives = $this->phpManager->config()->getSupportedDirectives();

        // Calculate quick summary metrics
        $customizedCount = count(array_filter($directives, fn($d) => $d['is_customized']));
        $totalDirectives = count($directives);

        $stats = [
            'total_directives' => $totalDirectives,
            'customized_count' => $customizedCount,
            'default_count' => $totalDirectives - $customizedCount,
            'fpm_running' => $discovered[$selectedVersion]['fpm_status'] ?? 'unknown',
            'memory_limit' => $directives['memory_limit']['value'] ?? '512M',
            'upload_max' => $directives['upload_max_filesize']['value'] ?? '256M',
            'max_exec' => $directives['max_execution_time']['value'] ?? '300',
        ];

        return Inertia::render('Admin/PHP/Configuration', [
            'directives' => $directives,
            'rawIni' => $rawIni,
            'supportedDirectives' => $supportedDirectives,
            'stats' => $stats,
            'selectedVersion' => $selectedVersion,
            'installedVersions' => $installedVersions,
            'defaultVersion' => \App\Models\SystemSetting::get('default_php_version', '8.5'),
        ]);
    }

    /**
     * Reset custom php.ini configuration to system defaults.
     */
    public function resetConfiguration(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->phpManager->config()->resetToDefaults($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Save Raw INI content.
     */
    public function updateRawConfiguration(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
            'raw_content' => 'required|string',
        ]);

        $res = $this->phpManager->config()->updateRawIni($validated['version'], $validated['raw_content'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }

    /**
     * Render the Per-Site PHP Version and Socket Mapping View.
     */
    public function perSite(Request $request): Response
    {
        $websites = Website::with(['subscription.user', 'subscription.plan'])
            ->latest()
            ->get();

        $discovered = $this->phpManager->discovery()->discoverInstalledVersions();
        $installedVersions = array_keys($discovered);
        $defaultVersion = \App\Models\SystemSetting::get('default_php_version', '8.5');

        $driftReports = $this->phpManager->sync()->detectDrift();

        $totalWebsites = $websites->count();
        $defaultCount = $websites->where('php_version', $defaultVersion)->count();
        $legacyCount = $websites->filter(fn($w) => in_array($w->php_version, ['7.4', '8.0']))->count();
        $driftCount = count($driftReports);

        $stats = [
            'total_websites' => $totalWebsites,
            'default_count' => $defaultCount,
            'legacy_count' => $legacyCount,
            'drift_count' => $driftCount,
            'default_version' => $defaultVersion,
        ];

        return Inertia::render('Admin/PHP/PerSite', [
            'websites' => $websites,
            'installedVersions' => $installedVersions,
            'defaultVersion' => $defaultVersion,
            'driftReports' => $driftReports,
            'stats' => $stats,
        ]);
    }

    /**
     * Batch Switch PHP Version for multiple websites.
     */
    public function batchSwitchSiteVersion(Request $request)
    {
        $validated = $request->validate([
            'website_ids' => 'required|array|min:1',
            'website_ids.*' => 'required|exists:websites,id',
            'target_version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $switched = 0;
        $failed = 0;

        foreach ($validated['website_ids'] as $id) {
            $site = Website::find($id);
            if ($site && $site->php_version !== $validated['target_version']) {
                $res = $this->phpManager->switchWebsitePhpVersion($site, $validated['target_version'], auth()->id());
                if ($res['success']) {
                    $switched++;
                } else {
                    $failed++;
                }
            }
        }

        if ($failed === 0) {
            return redirect()->back()->with('success', "{$switched} website(s) successfully switched to PHP {$validated['target_version']}.");
        }

        return redirect()->back()->with('success', "Switched {$switched} website(s). {$failed} failed (check audit logs).");
    }

    /**
     * Render the PHP Logs & Audit Trail View.
     */
    public function logs(Request $request): Response
    {
        $discovered = $this->phpManager->discovery()->discoverInstalledVersions();
        $installedVersions = array_keys($discovered);
        
        $selectedVersion = $request->query('version');
        if (!$selectedVersion || !in_array($selectedVersion, $installedVersions, true)) {
            $selectedVersion = \App\Models\SystemSetting::get('default_php_version', $installedVersions[0] ?? '8.5');
        }

        $lines = (int)$request->query('lines', 100);
        $levelFilter = $request->query('level', 'all');
        $search = $request->query('search');

        $fpmLogs = $this->logService->getFpmLogs($selectedVersion, $lines, $levelFilter);
        $auditLogs = $this->logService->getAuditLogs(60, $search);
        $stats = $this->logService->getLogStats($selectedVersion);

        return Inertia::render('Admin/PHP/Logs', [
            'fpmLogs' => $fpmLogs,
            'auditLogs' => $auditLogs,
            'stats' => $stats,
            'selectedVersion' => $selectedVersion,
            'installedVersions' => $installedVersions,
            'defaultVersion' => \App\Models\SystemSetting::get('default_php_version', '8.5'),
            'currentLines' => $lines,
            'currentLevel' => $levelFilter,
        ]);
    }

    /**
     * Clear / Truncate PHP-FPM service error log.
     */
    public function clearLogs(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^[0-9]+\.[0-9]+$/',
        ]);

        $res = $this->logService->clearFpmLogs($validated['version'], auth()->id());

        if ($res['success']) {
            return redirect()->back()->with('success', $res['message']);
        }

        return redirect()->back()->withErrors(['error' => $res['error']]);
    }
}
