<?php

namespace App\Services\PHP;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpDiscoveryService
{
    use CommandExecutor;

    protected string $phpConfigBase = '/etc/php';
    protected string $phpSocketBase = '/run/php';

    /**
     * Dynamically discover all installed PHP versions, services, sockets, and configs.
     *
     * @return array<string, array>
     */
    public function discoverInstalledVersions(): array
    {
        $discovered = [];

        // 1. Scan /etc/php directory for version folders
        if (File::isDirectory($this->phpConfigBase)) {
            $directories = File::directories($this->phpConfigBase);

            foreach ($directories as $dir) {
                $version = basename($dir);

                // Ensure it looks like a valid PHP version number (e.g. 8.1, 8.2, 8.3, 8.5, 8.6)
                if (preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
                    $discovered[$version] = $this->inspectVersion($version, $dir);
                }
            }
        }

        // 2. Also check if standard PHP binaries exist in /usr/bin/php*
        $binaries = glob('/usr/bin/php[0-9].[0-9]');
        if ($binaries) {
            foreach ($binaries as $bin) {
                if (preg_match('/php([0-9]+\.[0-9]+)$/', $bin, $matches)) {
                    $version = $matches[1];
                    if (!isset($discovered[$version])) {
                        $configDir = $this->phpConfigBase . '/' . $version;
                        $discovered[$version] = $this->inspectVersion($version, $configDir);
                    }
                }
            }
        }

        // Sort versions descending (e.g. 8.5, 8.3, 8.2)
        krsort($discovered, SORT_NATURAL);

        return $discovered;
    }

    /**
     * Inspect a specific PHP version environment (CLI vs FPM).
     */
    public function inspectVersion(string $version, ?string $configDir = null): array
    {
        $configDir = $configDir ?: ($this->phpConfigBase . '/' . $version);
        $cliBinary = "/usr/bin/php{$version}";
        $fpmBinary = "/usr/sbin/php-fpm{$version}";
        $fpmService = "php{$version}-fpm";
        $fpmSocket = "{$this->phpSocketBase}/php{$version}-fpm.sock";
        $fpmIni = "{$configDir}/fpm/php.ini";
        $cliIni = "{$configDir}/cli/php.ini";
        $fpmConfDir = "{$configDir}/fpm/conf.d";

        $hasCli = file_exists($cliBinary);
        $hasFpm = file_exists($fpmBinary) || file_exists($fpmIni) || file_exists("/lib/systemd/system/{$fpmService}.service") || file_exists("/usr/lib/systemd/system/{$fpmService}.service");
        $hasSocket = file_exists($fpmSocket);

        // Fetch CLI exact version string
        $cliExactVersion = null;
        if ($hasCli) {
            $cliVersionResult = $this->executeCommand([$cliBinary, '-r', 'echo PHP_VERSION;']);
            if ($cliVersionResult['success']) {
                $cliExactVersion = trim($cliVersionResult['output']);
            }
        }

        // Get systemd FPM service live status
        $fpmStatus = $this->getFpmServiceStatus($fpmService);

        // Discovered extensions for this version
        $extensions = $this->getLoadedExtensions($version, $cliBinary);

        // Check OPcache status for this version
        $opcacheEnabled = in_array('Zend OPcache', $extensions) || in_array('opcache', array_map('strtolower', $extensions));

        return [
            'version' => $version,
            'major_version' => explode('.', $version)[0] ?? '8',
            'minor_version' => explode('.', $version)[1] ?? '0',
            'cli_binary' => $hasCli ? $cliBinary : null,
            'cli_version' => $cliExactVersion ?: $version,
            'fpm_binary' => $hasFpm ? $fpmBinary : null,
            'fpm_service' => $fpmService,
            'fpm_socket' => $fpmSocket,
            'socket_exists' => $hasSocket,
            'socket_status' => $hasSocket ? 'active' : 'missing',
            'fpm_status' => $fpmStatus['status'],
            'fpm_details' => $fpmStatus,
            'fpm_ini' => file_exists($fpmIni) ? $fpmIni : null,
            'cli_ini' => file_exists($cliIni) ? $cliIni : null,
            'fpm_conf_dir' => is_dir($fpmConfDir) ? $fpmConfDir : null,
            'custom_ini_path' => "{$fpmConfDir}/99-deeptouchhost-custom.ini",
            'has_custom_ini' => file_exists("{$fpmConfDir}/99-deeptouchhost-custom.ini"),
            'extensions' => $extensions,
            'opcache_enabled' => $opcacheEnabled,
            'is_healthy' => $fpmStatus['status'] === 'running' && $hasSocket,
        ];
    }

    /**
     * Get systemd status telemetry for PHP-FPM service.
     */
    public function getFpmServiceStatus(string $serviceName): array
    {
        $default = [
            'status' => 'unknown',
            'active_state' => 'unknown',
            'sub_state' => 'unknown',
            'pid' => 0,
            'uptime' => '0s',
            'active_timestamp' => null,
            'workers' => 0,
            'memory_bytes' => 0,
            'memory_formatted' => '0 MB',
        ];

        // 1. Check systemctl is-active
        $isActive = $this->executeSudoCommand(['systemctl', 'is-active', $serviceName]);
        $state = trim($isActive['output'] ?? '');

        if ($state === 'active') {
            $default['status'] = 'running';
            $default['active_state'] = 'active';
        } elseif ($state === 'inactive' || $state === 'failed') {
            $default['status'] = 'stopped';
            $default['active_state'] = $state;
        } else {
            $default['status'] = $state ?: 'unknown';
            $default['active_state'] = $state ?: 'unknown';
        }

        // 2. Fetch structured properties via systemctl show
        $showResult = $this->executeSudoCommand([
            'systemctl', 'show', $serviceName,
            '--property=ActiveState,SubState,MainPID,ActiveEnterTimestamp,MemoryCurrent'
        ]);

        if ($showResult['success'] && !empty($showResult['output'])) {
            $lines = explode("\n", trim($showResult['output']));
            foreach ($lines as $line) {
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);

                    if ($key === 'ActiveState') {
                        $default['active_state'] = $val;
                    } elseif ($key === 'SubState') {
                        $default['sub_state'] = $val;
                    } elseif ($key === 'MainPID') {
                        $default['pid'] = (int)$val;
                    } elseif ($key === 'ActiveEnterTimestamp') {
                        $default['active_timestamp'] = $val;
                    } elseif ($key === 'MemoryCurrent' && is_numeric($val)) {
                        $bytes = (int)$val;
                        $default['memory_bytes'] = $bytes;
                        $default['memory_formatted'] = round($bytes / 1024 / 1024, 1) . ' MB';
                    }
                }
            }
        }

        // Count worker processes for this PHP-FPM service
        if ($default['pid'] > 0) {
            $psResult = $this->executeCommand(['pgrep', '-P', (string)$default['pid']]);
            if ($psResult['success'] && !empty($psResult['output'])) {
                $workers = array_filter(explode("\n", trim($psResult['output'])));
                $default['workers'] = count($workers);
            }
        }

        return $default;
    }

    /**
     * Get loaded extensions for a PHP version.
     */
    public function getLoadedExtensions(string $version, ?string $cliBinary = null): array
    {
        $cliBinary = $cliBinary ?: "/usr/bin/php{$version}";

        if (file_exists($cliBinary)) {
            $res = $this->executeCommand([$cliBinary, '-m']);
            if ($res['success']) {
                $lines = explode("\n", trim($res['output']));
                $modules = [];
                $inZend = false;

                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (empty($trimmed)) continue;
                    if ($trimmed === '[PHP Modules]') continue;
                    if ($trimmed === '[Zend Modules]') {
                        $inZend = true;
                        continue;
                    }
                    $modules[] = $trimmed;
                }

                sort($modules, SORT_NATURAL | SORT_FLAG_CASE);
                return array_values(array_unique($modules));
            }
        }

        // Fallback: check mods-available or conf.d
        $modsDir = "{$this->phpConfigBase}/{$version}/fpm/conf.d";
        if (File::isDirectory($modsDir)) {
            $files = File::files($modsDir);
            $mods = [];
            foreach ($files as $f) {
                $name = preg_replace('/^[0-9]+-/', '', $f->getFilename());
                $name = str_replace('.ini', '', $name);
                if ($name !== 'deeptouchhost-custom') {
                    $mods[] = $name;
                }
            }
            sort($mods, SORT_NATURAL);
            return array_values(array_unique($mods));
        }

        return [];
    }
}
