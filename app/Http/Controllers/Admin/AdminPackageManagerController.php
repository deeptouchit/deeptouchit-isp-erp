<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class AdminPackageManagerController extends Controller
{
    /**
     * Primary hosting software packages of interest.
     */
    protected array $corePackageKeywords = [
        'nginx' => 'Web Stack',
        'apache2' => 'Web Stack',
        'certbot' => 'Web Stack',
        'mysql-server' => 'Databases',
        'mariadb-server' => 'Databases',
        'redis-server' => 'Databases',
        'postgresql' => 'Databases',
        'php8.2-fpm' => 'PHP Engines',
        'php8.2-cli' => 'PHP Engines',
        'php8.3-fpm' => 'PHP Engines',
        'php8.3-cli' => 'PHP Engines',
        'php8.5-fpm' => 'PHP Engines',
        'php8.5-cli' => 'PHP Engines',
        'composer' => 'PHP Engines',
        'postfix' => 'Mail & FTP',
        'vsftpd' => 'Mail & FTP',
        'dovecot' => 'Mail & FTP',
        'openssh-server' => 'Security & SSH',
        'ufw' => 'Security & SSH',
        'fail2ban' => 'Security & SSH',
        'iptables' => 'Security & SSH',
        'bind9' => 'Security & SSH',
        'git' => 'System Core',
        'curl' => 'System Core',
        'htop' => 'System Core',
        'systemd' => 'System Core',
    ];

    /**
     * Display live APT Package Manager.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        // Fetch upgradable packages map
        $upgradableMap = $this->getUpgradablePackagesMap();
        $upgradableCount = count($upgradableMap);
        $securityCount = count(array_filter($upgradableMap, fn($u) => $u['is_security']));

        // Get installed packages
        $packages = $this->getInstalledPackages($upgradableMap);
        $totalInstalled = count($packages);

        // Active Repositories Count
        $repoCount = $this->getActiveRepositoriesCount();

        $stats = [
            'total_installed' => $totalInstalled,
            'upgradable_count' => $upgradableCount,
            'security_count' => $securityCount,
            'active_repositories' => $repoCount,
            'architecture' => php_uname('m') ?: 'x86_64',
        ];

        // Filter: Category
        $currentCategory = $request->input('category', 'all');
        if ($currentCategory === 'upgradable') {
            $packages = array_filter($packages, fn($p) => $p['is_upgradable']);
        } elseif ($currentCategory === 'core_hosting') {
            $packages = array_filter($packages, fn($p) => $p['is_core']);
        } elseif ($currentCategory !== 'all' && $currentCategory !== '') {
            $packages = array_filter($packages, fn($p) => $p['category'] === $currentCategory);
        }

        // Filter: Search
        $search = trim($request->input('search', ''));
        if ($search !== '') {
            $packages = array_filter($packages, function ($p) use ($search) {
                return str_contains(strtolower($p['package']), strtolower($search))
                    || str_contains(strtolower($p['summary']), strtolower($search))
                    || str_contains(strtolower($p['version']), strtolower($search))
                    || str_contains(strtolower($p['category']), strtolower($search));
            });
        }

        // Pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 25;
        $totalItems = count($packages);
        $pagedData = array_slice(array_values($packages), ($page - 1) * $perPage, $perPage);

        $paginated = new LengthAwarePaginator(
            $pagedData,
            $totalItems,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = ['Web Stack', 'Databases', 'PHP Engines', 'Mail & FTP', 'Security & SSH', 'System Core'];

        return Inertia::render('Admin/RootTools/Packages/Index', [
            'packages' => $paginated,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $currentCategory,
            ],
        ]);
    }

    /**
     * Update package lists (apt-get update).
     */
    public function updateIndex(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        if (app()->environment('testing')) {
            $returnCode = 0;
            $output = ['Hit:1 http://archive.ubuntu.com resolute InRelease', 'Reading package lists... Done'];
        } else {
            exec('apt-get update --no-ask-password 2>&1', $output, $returnCode);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'apt_update_index',
            'description' => 'Updated APT repository package index.',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['return_code' => $returnCode, 'output' => implode("\n", array_slice($output, -10))],
        ]);

        if ($returnCode === 0) {
            return redirect()->route('admin.root-tools.packages')
                ->with('success', 'APT repository cache updated successfully.');
        }

        $errorMsg = !empty($output) ? implode(' ', array_slice($output, -5)) : 'Failed to update index';
        return redirect()->route('admin.root-tools.packages')
            ->with('error', "Failed to refresh APT index: {$errorMsg}");
    }

    /**
     * Upgrade a specific package.
     */
    public function upgradePackage(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'package' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\.\-\+_]+$/'],
        ]);

        $package = $validated['package'];

        if (app()->environment('testing')) {
            $returnCode = 0;
            $output = ["Preparing to unpack {$package} ...", "Setting up {$package} ..."];
        } else {
            $safePkg = escapeshellarg($package);
            exec("apt-get install --only-upgrade -y {$safePkg} 2>&1", $output, $returnCode);
        }

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'apt_upgrade_package',
            'description' => "Triggered APT package upgrade for {$package}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['package' => $package],
            'new_values' => ['return_code' => $returnCode, 'output' => implode("\n", array_slice($output, -10))],
        ]);

        if ($returnCode === 0) {
            return redirect()->route('admin.root-tools.packages')
                ->with('success', "Package '{$package}' successfully upgraded.");
        }

        $errorMsg = !empty($output) ? implode(' ', array_slice($output, -5)) : 'Upgrade failed';
        return redirect()->route('admin.root-tools.packages')
            ->with('error', "Failed to upgrade {$package}: {$errorMsg}");
    }

    /**
     * Inspect detailed package metadata with dpkg -s.
     */
    public function details(Request $request, string $package): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        if (!preg_match('/^[a-zA-Z0-9\.\-\+_]+$/', $package)) {
            return response()->json(['error' => 'Invalid package name.'], 400);
        }

        $safePkg = escapeshellarg($package);
        $output = shell_exec("dpkg -s {$safePkg} 2>/dev/null");

        if (!$output) {
            return response()->json(['error' => "Package '{$package}' not found or not installed."], 404);
        }

        $meta = [];
        $lines = explode("\n", trim($output));
        $currentKey = '';

        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z0-9\-]+):\s*(.*)$/', $line, $matches)) {
                $currentKey = $matches[1];
                $meta[$currentKey] = $matches[2];
            } elseif ($currentKey && str_starts_with($line, ' ')) {
                $meta[$currentKey] .= "\n" . trim($line);
            }
        }

        return response()->json([
            'package' => $package,
            'version' => $meta['Version'] ?? 'Unknown',
            'architecture' => $meta['Architecture'] ?? 'all',
            'installed_size' => isset($meta['Installed-Size']) ? round(((int) $meta['Installed-Size']) / 1024, 2) . ' MB' : 'N/A',
            'section' => $meta['Section'] ?? 'General',
            'maintainer' => $meta['Maintainer'] ?? 'Ubuntu Developers',
            'homepage' => $meta['Homepage'] ?? null,
            'description' => $meta['Description'] ?? 'No description available.',
            'depends' => $meta['Depends'] ?? 'None',
        ]);
    }

    /**
     * Parse installed packages via dpkg-query.
     */
    private function getInstalledPackages(array $upgradableMap): array
    {
        $raw = shell_exec("dpkg-query -W -f='\${Package}|\${Version}|\${Architecture}|\${Installed-Size}|\${Status}|\${binary:Summary}\\n' 2>/dev/null");
        if (!$raw) {
            return [];
        }

        $lines = explode("\n", trim($raw));
        $list = [];

        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = explode('|', $line, 6);
            if (count($parts) < 6) continue;

            $pkgName = $parts[0];
            $version = $parts[1];
            $arch = $parts[2];
            $sizeKb = (int) $parts[3];
            $status = $parts[4];
            $summary = $parts[5];

            // Only consider installed packages
            if (!str_contains($status, 'installed')) {
                continue;
            }

            $sizeFormatted = $sizeKb > 1024 
                ? round($sizeKb / 1024, 1) . ' MB' 
                : $sizeKb . ' KB';

            $category = $this->determineCategory($pkgName);
            $isCore = isset($this->corePackageKeywords[$pkgName]);
            $isUpgradable = isset($upgradableMap[$pkgName]);
            $candidateVersion = $isUpgradable ? $upgradableMap[$pkgName]['candidate'] : null;

            $list[] = [
                'package' => $pkgName,
                'version' => $version,
                'architecture' => $arch,
                'size' => $sizeFormatted,
                'size_kb' => $sizeKb,
                'summary' => $summary ?: 'Linux software package',
                'category' => $category,
                'is_core' => $isCore,
                'is_upgradable' => $isUpgradable,
                'candidate_version' => $candidateVersion,
            ];
        }

        // Sort: Upgradable first, then core packages, then alphabetically
        usort($list, function ($a, $b) {
            if ($a['is_upgradable'] !== $b['is_upgradable']) {
                return $a['is_upgradable'] ? -1 : 1;
            }
            if ($a['is_core'] !== $b['is_core']) {
                return $a['is_core'] ? -1 : 1;
            }
            return strcmp($a['package'], $b['package']);
        });

        return $list;
    }

    /**
     * Parse upgradable packages using apt list --upgradable.
     */
    private function getUpgradablePackagesMap(): array
    {
        $raw = shell_exec('apt list --upgradable 2>/dev/null');
        if (!$raw) {
            return [];
        }

        $lines = explode("\n", trim($raw));
        $map = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Listing...') || empty($line)) {
                continue;
            }

            // e.g. apparmor/resolute-updates 5.0.2-0ubuntu1~26.04.1 amd64 [upgradable from: 5.0.0~beta1-0ubuntu7]
            if (preg_match('/^([^\/\s]+)\/([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+\[upgradable from:\s*([^\]]+)\]/', $line, $matches)) {
                $pkg = $matches[1];
                $repo = $matches[2];
                $candidate = $matches[3];
                $isSecurity = str_contains(strtolower($repo), 'security');

                $map[$pkg] = [
                    'package' => $pkg,
                    'repo' => $repo,
                    'candidate' => $candidate,
                    'is_security' => $isSecurity,
                ];
            }
        }

        return $map;
    }

    /**
     * Map package name to category.
     */
    private function determineCategory(string $pkg): string
    {
        if (isset($this->corePackageKeywords[$pkg])) {
            return $this->corePackageKeywords[$pkg];
        }

        if (str_starts_with($pkg, 'php')) return 'PHP Engines';
        if (str_contains($pkg, 'nginx') || str_contains($pkg, 'apache') || str_contains($pkg, 'certbot')) return 'Web Stack';
        if (str_contains($pkg, 'mysql') || str_contains($pkg, 'mariadb') || str_contains($pkg, 'redis') || str_contains($pkg, 'postgres')) return 'Databases';
        if (str_contains($pkg, 'mail') || str_contains($pkg, 'postfix') || str_contains($pkg, 'ftp')) return 'Mail & FTP';
        if (str_contains($pkg, 'ssh') || str_contains($pkg, 'firewall') || str_contains($pkg, 'ufw') || str_contains($pkg, 'security') || str_contains($pkg, 'crypto')) return 'Security & SSH';

        return 'System Core';
    }

    /**
     * Get active repository sources count.
     */
    private function getActiveRepositoriesCount(): int
    {
        $count = 0;
        if (file_exists('/etc/apt/sources.list')) {
            $count++;
        }
        $sourcesDir = '/etc/apt/sources.list.d';
        if (is_dir($sourcesDir)) {
            $files = glob("{$sourcesDir}/*.{list,sources}", GLOB_BRACE);
            $count += count($files);
        }
        return max(1, $count);
    }
}
