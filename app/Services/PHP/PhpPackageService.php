<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Website;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class PhpPackageService
{
    use CommandExecutor;

    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Catalog of supported PHP versions and release lifecycle metadata.
     */
    public function getAvailableCatalog(): array
    {
        $discovered = $this->discoveryService->discoverInstalledVersions();
        $defaultVersion = SystemSetting::get('default_php_version', config('panel.hosting.default_php_version', '8.5'));

        $matrix = [
            '8.5' => [
                'name' => 'PHP 8.5',
                'status' => 'Latest Release',
                'status_type' => 'latest', // latest, active, security, eol
                'release_date' => 'Nov 2025',
                'eol_date' => 'Nov 2028',
                'description' => 'Bleeding-edge PHP runtime with modern JIT, typed closures, and top performance.',
                'is_lts' => true,
                'recommended' => true,
            ],
            '8.4' => [
                'name' => 'PHP 8.4',
                'status' => 'Active Support',
                'status_type' => 'active',
                'release_date' => 'Nov 2024',
                'eol_date' => 'Nov 2027',
                'description' => 'Property hooks, asymmetric visibility, and lazy objects.',
                'is_lts' => true,
                'recommended' => false,
            ],
            '8.3' => [
                'name' => 'PHP 8.3',
                'status' => 'Active Support',
                'status_type' => 'active',
                'release_date' => 'Nov 2023',
                'eol_date' => 'Nov 2026',
                'description' => 'Typed class constants, json_validate(), and high stability.',
                'is_lts' => true,
                'recommended' => false,
            ],
            '8.2' => [
                'name' => 'PHP 8.2',
                'status' => 'Security Fixes Only',
                'status_type' => 'security',
                'release_date' => 'Dec 2022',
                'eol_date' => 'Dec 2025',
                'description' => 'Readonly classes, null/false standalone types, and DNF types.',
                'is_lts' => false,
                'recommended' => false,
            ],
            '8.1' => [
                'name' => 'PHP 8.1',
                'status' => 'Security Fixes Only',
                'status_type' => 'security',
                'release_date' => 'Nov 2021',
                'eol_date' => 'Nov 2024',
                'description' => 'Enums, readonly properties, fibers, and pure intersection types.',
                'is_lts' => false,
                'recommended' => false,
            ],
            '8.0' => [
                'name' => 'PHP 8.0',
                'status' => 'End of Life (Legacy)',
                'status_type' => 'eol',
                'release_date' => 'Nov 2020',
                'eol_date' => 'Nov 2023',
                'description' => 'Legacy runtime for older enterprise applications requiring PHP 8.0.',
                'is_lts' => false,
                'recommended' => false,
            ],
            '7.4' => [
                'name' => 'PHP 7.4',
                'status' => 'End of Life (Legacy)',
                'status_type' => 'eol',
                'release_date' => 'Nov 2019',
                'eol_date' => 'Nov 2022',
                'description' => 'Legacy PHP 7.x branch for backward compatibility with old scripts.',
                'is_lts' => false,
                'recommended' => false,
            ],
        ];

        $catalog = [];

        foreach ($matrix as $ver => $meta) {
            $isInstalled = isset($discovered[$ver]);
            $hostedSites = Website::where('php_version', $ver)->count();
            $fpmStatus = $isInstalled ? ($discovered[$ver]['fpm_status'] ?? 'unknown') : 'not_installed';

            $catalog[$ver] = array_merge($meta, [
                'version' => $ver,
                'is_installed' => $isInstalled,
                'is_default' => ($ver === $defaultVersion),
                'fpm_status' => $fpmStatus,
                'hosted_sites' => $hostedSites,
                'fpm_socket' => $isInstalled ? ($discovered[$ver]['fpm_socket'] ?? null) : null,
                'installed_extensions_count' => $isInstalled ? count($discovered[$ver]['extensions'] ?? []) : 0,
                'popular_extensions' => [
                    ['name' => 'mysql', 'installed' => $isInstalled && in_array('pdo_mysql', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'curl', 'installed' => $isInstalled && in_array('curl', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'gd', 'installed' => $isInstalled && in_array('gd', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'zip', 'installed' => $isInstalled && in_array('zip', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'mbstring', 'installed' => $isInstalled && in_array('mbstring', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'redis', 'installed' => $isInstalled && in_array('redis', $discovered[$ver]['extensions'] ?? [])],
                    ['name' => 'imagick', 'installed' => $isInstalled && in_array('imagick', $discovered[$ver]['extensions'] ?? [])],
                ],
            ]);
        }

        return $catalog;
    }

    /**
     * Install a PHP version and its standard hosting bundle.
     */
    public function installVersion(string $version, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => 'Invalid PHP version identifier.'];
        }

        $packages = [
            "php{$version}-fpm",
            "php{$version}-cli",
            "php{$version}-common",
            "php{$version}-mysql",
            "php{$version}-curl",
            "php{$version}-gd",
            "php{$version}-zip",
            "php{$version}-mbstring",
            "php{$version}-xml",
            "php{$version}-bcmath",
            "php{$version}-intl",
            "php{$version}-opcache",
            "php{$version}-soap",
        ];

        $packageList = implode(' ', $packages);

        $cmd = "sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l apt-get install -y -q -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold {$packageList} && sudo systemctl enable php{$version}-fpm && sudo systemctl restart php{$version}-fpm";

        $res = $this->executeCommand(['bash', '-c', $cmd]);

        if ($res['success']) {
            $this->logAction($adminId, 'php_version_installed', [
                'version' => $version,
                'packages' => $packages,
            ]);

            return [
                'success' => true,
                'message' => "PHP {$version} and core extension bundle installed successfully.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to install PHP {$version}: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    /**
     * Safely uninstall / purge a PHP version.
     */
    public function uninstallVersion(string $version, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => 'Invalid PHP version identifier.'];
        }

        // Safety 1: Check active websites
        $activeSitesCount = Website::where('php_version', $version)->count();
        if ($activeSitesCount > 0) {
            return [
                'success' => false,
                'error' => "Cannot remove PHP {$version}. There are {$activeSitesCount} active website(s) currently configured to use this engine. Please migrate them first.",
            ];
        }

        // Safety 2: Check server default version
        $defaultVersion = SystemSetting::get('default_php_version', '8.5');
        if ($version === $defaultVersion) {
            return [
                'success' => false,
                'error' => "Cannot remove PHP {$version} because it is currently set as the Server Default runtime.",
            ];
        }

        $packages = [
            "php{$version}-fpm",
            "php{$version}-cli",
            "php{$version}-common",
            "php{$version}-mysql",
            "php{$version}-curl",
            "php{$version}-gd",
            "php{$version}-zip",
            "php{$version}-mbstring",
            "php{$version}-xml",
            "php{$version}-bcmath",
            "php{$version}-intl",
            "php{$version}-opcache",
            "php{$version}-soap",
            "php{$version}-readline",
        ];

        $packageList = implode(' ', $packages);

        $cmd = "sudo systemctl stop php{$version}-fpm 2>/dev/null; sudo systemctl disable php{$version}-fpm 2>/dev/null; sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l apt-get purge -y -q {$packageList} 2>/dev/null; sudo rm -rf /etc/php/{$version} /run/php/php{$version}-fpm.sock /var/log/php{$version}-fpm.log";

        $res = $this->executeCommand(['bash', '-c', $cmd]);

        if ($res['success']) {
            $this->logAction($adminId, 'php_version_uninstalled', [
                'version' => $version,
            ]);

            return [
                'success' => true,
                'message' => "PHP {$version} has been purged and removed from the system.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to uninstall PHP {$version}: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    /**
     * Install a single PHP extension.
     */
    public function installExtension(string $version, string $extension, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version) || !preg_match('/^[a-zA-Z0-9_-]+$/', $extension)) {
            return ['success' => false, 'error' => 'Invalid version or extension name.'];
        }

        $pkg = "php{$version}-{$extension}";
        $cmd = "sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l apt-get install -y -q {$pkg} && sudo systemctl reload php{$version}-fpm";

        $res = $this->executeCommand(['bash', '-c', $cmd]);

        if ($res['success']) {
            $this->logAction($adminId, 'php_extension_installed', [
                'version' => $version,
                'extension' => $extension,
            ]);

            return [
                'success' => true,
                'message' => "Extension '{$extension}' installed for PHP {$version}.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to install extension: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    /**
     * Remove a single PHP extension.
     */
    public function removeExtension(string $version, string $extension, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version) || !preg_match('/^[a-zA-Z0-9_-]+$/', $extension)) {
            return ['success' => false, 'error' => 'Invalid version or extension name.'];
        }

        $pkg = "php{$version}-{$extension}";
        $cmd = "sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=l apt-get purge -y -q {$pkg} && sudo systemctl reload php{$version}-fpm";

        $res = $this->executeCommand(['bash', '-c', $cmd]);

        if ($res['success']) {
            $this->logAction($adminId, 'php_extension_removed', [
                'version' => $version,
                'extension' => $extension,
            ]);

            return [
                'success' => true,
                'message' => "Extension '{$extension}' removed from PHP {$version}.",
            ];
        }

        return [
            'success' => false,
            'error' => "Failed to remove extension: " . ($res['error_output'] ?: $res['error']),
        ];
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PHP Package Manager: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to log activity: " . $e->getMessage());
        }
    }
}
