<?php

namespace App\Services\PHP;

use App\Models\Website;
use Illuminate\Support\Facades\File;

class PhpCompatibilityService
{
    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Check compatibility of a website when switching to a target PHP version.
     *
     * @param Website $website
     * @param string $targetVersion
     * @return array
     */
    public function checkCompatibility(Website $website, string $targetVersion): array
    {
        $docRoot = $website->document_root;
        $appType = 'Custom Application';
        $appVersion = null;
        $requiredPhp = null;
        $requiredExtensions = ['pdo_mysql', 'mbstring', 'curl', 'openssl', 'json'];
        $warnings = [];
        $unsupported = [];

        // 1. Detect WordPress
        if (File::exists("{$docRoot}/wp-config.php") || File::exists(dirname($docRoot) . "/wp-config.php")) {
            $appType = 'WordPress';
            $wpVersionFile = "{$docRoot}/wp-includes/version.php";
            if (File::exists($wpVersionFile)) {
                $content = File::get($wpVersionFile);
                if (preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) {
                    $appVersion = $m[1];
                }
            }
            $requiredExtensions = array_merge($requiredExtensions, ['mysqli', 'gd', 'xml', 'zip', 'imagick']);
        } 
        // 2. Detect Laravel
        elseif (File::exists("{$docRoot}/../artisan") || File::exists("{$docRoot}/artisan") || File::exists("{$docRoot}/../composer.json")) {
            $appType = 'Laravel Framework';
            $composerPath = File::exists("{$docRoot}/../composer.json") ? "{$docRoot}/../composer.json" : "{$docRoot}/composer.json";
            
            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);
                $requiredPhp = $composer['require']['php'] ?? null;
                $laravelPkg = $composer['require']['laravel/framework'] ?? null;
                if ($laravelPkg) {
                    $appVersion = "Laravel " . str_replace(['^', '~'], '', $laravelPkg);
                }
            }
            $requiredExtensions = array_merge($requiredExtensions, ['pdo', 'tokenizer', 'xml', 'ctype', 'bcmath', 'fileinfo']);
        }

        // 3. Verify target PHP version installed and extensions available
        $discovered = $this->discoveryService->discoverInstalledVersions();
        if (!isset($discovered[$targetVersion])) {
            return [
                'status' => 'unsupported',
                'app_type' => $appType,
                'app_version' => $appVersion,
                'target_version' => $targetVersion,
                'reasons' => ["PHP {$targetVersion} is not installed on this server."],
                'missing_extensions' => [],
            ];
        }

        $installedExtensions = array_map('strtolower', $discovered[$targetVersion]['extensions'] ?? []);
        $missingExtensions = [];

        foreach ($requiredExtensions as $reqExt) {
            if (!in_array(strtolower($reqExt), $installedExtensions, true)) {
                $missingExtensions[] = $reqExt;
            }
        }

        if (!empty($missingExtensions)) {
            $warnings[] = "Target PHP {$targetVersion} may lack recommended module(s): " . implode(', ', $missingExtensions);
        }

        // Target version checks
        $targetFloat = (float)$targetVersion;
        if ($targetFloat >= 8.5 && $appType === 'WordPress' && $appVersion && version_compare($appVersion, '6.5', '<')) {
            $warnings[] = "WordPress {$appVersion} has limited testing on PHP {$targetVersion}. Recommend upgrading WP core.";
        }

        $status = 'compatible';
        if (!empty($unsupported)) {
            $status = 'unsupported';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'app_type' => $appType,
            'app_version' => $appVersion,
            'target_version' => $targetVersion,
            'reasons' => array_merge($unsupported, $warnings),
            'missing_extensions' => $missingExtensions,
            'checks' => [
                'php_installed' => true,
                'fpm_active' => $discovered[$targetVersion]['fpm_status'] === 'running',
                'socket_ready' => $discovered[$targetVersion]['socket_exists'],
                'core_extensions_loaded' => empty($missingExtensions),
            ]
        ];
    }
}
