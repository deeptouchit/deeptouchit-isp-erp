<?php

namespace App\Services\PHP;

use App\Models\ActivityLog;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PhpExtensionService
{
    use CommandExecutor;

    protected PhpDiscoveryService $discovery;
    protected PhpFpmService $fpmService;

    public function __construct(PhpDiscoveryService $discovery, PhpFpmService $fpmService)
    {
        $this->discovery = $discovery;
        $this->fpmService = $fpmService;
    }

    /**
     * Master Extension Dictionary with rich metadata.
     */
    protected function getExtensionDefinitions(): array
    {
        return [
            // Database & Cache
            'pdo_mysql' => [
                'name' => 'pdo_mysql',
                'title' => 'MySQL PDO Driver',
                'category' => 'Database & Cache',
                'package' => 'mysql',
                'description' => 'PHP Data Objects (PDO) driver for MySQL and MariaDB databases.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'mysqli' => [
                'name' => 'mysqli',
                'title' => 'MySQL Improved',
                'category' => 'Database & Cache',
                'package' => 'mysql',
                'description' => 'Native improved driver for interacting with MySQL and MariaDB database servers.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'pgsql' => [
                'name' => 'pgsql',
                'title' => 'PostgreSQL Driver',
                'category' => 'Database & Cache',
                'package' => 'pgsql',
                'description' => 'Standard driver for connecting and executing queries on PostgreSQL servers.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'pdo_pgsql' => [
                'name' => 'pdo_pgsql',
                'title' => 'PostgreSQL PDO Driver',
                'category' => 'Database & Cache',
                'package' => 'pgsql',
                'description' => 'PHP Data Objects driver for PostgreSQL database engines.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'sqlite3' => [
                'name' => 'sqlite3',
                'title' => 'SQLite 3 Database',
                'category' => 'Database & Cache',
                'package' => 'sqlite3',
                'description' => 'Embedded serverless SQL database engine interface for local fast storage.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'pdo_sqlite' => [
                'name' => 'pdo_sqlite',
                'title' => 'SQLite PDO Driver',
                'category' => 'Database & Cache',
                'package' => 'sqlite3',
                'description' => 'PDO abstraction interface for SQLite database files.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'redis' => [
                'name' => 'redis',
                'title' => 'Redis In-Memory Cache',
                'category' => 'Database & Cache',
                'package' => 'redis',
                'description' => 'High-performance Redis client for WordPress object caching, session storage, and queues.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'memcached' => [
                'name' => 'memcached',
                'title' => 'Memcached Cache Client',
                'category' => 'Database & Cache',
                'package' => 'memcached',
                'description' => 'Fast distributed memory object caching system for high-concurrency web apps.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'mongodb' => [
                'name' => 'mongodb',
                'title' => 'MongoDB NoSQL Driver',
                'category' => 'Database & Cache',
                'package' => 'mongodb',
                'description' => 'Official PHP driver for document-oriented MongoDB database clusters.',
                'is_core' => false,
                'is_recommended' => false,
            ],

            // Graphics & Media
            'gd' => [
                'name' => 'gd',
                'title' => 'GD Graphics Library',
                'category' => 'Graphics & Media',
                'package' => 'gd',
                'description' => 'Dynamic image creation and manipulation library (JPEG, PNG, WebP, AVIF, GIF).',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'imagick' => [
                'name' => 'imagick',
                'title' => 'ImageMagick Wrapper',
                'category' => 'Graphics & Media',
                'package' => 'imagick',
                'description' => 'Advanced image processing, PDF rendering, color manipulation, and raster/vector transforms.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'exif' => [
                'name' => 'exif',
                'title' => 'EXIF Metadata Reader',
                'category' => 'Graphics & Media',
                'package' => 'common',
                'description' => 'Reads Exchangeable Image File (EXIF) metadata headers from digital photos.',
                'is_core' => false,
                'is_recommended' => true,
            ],

            // Compression & Archives
            'zip' => [
                'name' => 'zip',
                'title' => 'Zip Archive Compression',
                'category' => 'Compression & Archives',
                'package' => 'zip',
                'description' => 'Create, read, and extract standard ZIP compressed archives and packages.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'bz2' => [
                'name' => 'bz2',
                'title' => 'Bzip2 Compression',
                'category' => 'Compression & Archives',
                'package' => 'bz2',
                'description' => 'High-ratio Bzip2 file compression and decompression routines.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'zlib' => [
                'name' => 'zlib',
                'title' => 'Zlib Gzip Compression',
                'category' => 'Compression & Archives',
                'package' => 'common',
                'description' => 'Gzip HTTP payload compression and deflation streaming library.',
                'is_core' => true,
                'is_recommended' => true,
            ],
            'phar' => [
                'name' => 'phar',
                'title' => 'PHAR Archive Support',
                'category' => 'Compression & Archives',
                'package' => 'common',
                'description' => 'Executes self-contained PHP applications packaged as PHAR archives (Composer, WP-CLI).',
                'is_core' => true,
                'is_recommended' => true,
            ],

            // Web Services & API
            'curl' => [
                'name' => 'curl',
                'title' => 'cURL HTTP Client',
                'category' => 'Web Services & API',
                'package' => 'curl',
                'description' => 'Sends HTTP/HTTPS/FTP API requests, webhooks, REST calls, and remote downloads.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'soap' => [
                'name' => 'soap',
                'title' => 'SOAP Protocol Protocol',
                'category' => 'Web Services & API',
                'package' => 'soap',
                'description' => 'Builds and consumes XML SOAP web services for payment gateways and enterprise APIs.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'sockets' => [
                'name' => 'sockets',
                'title' => 'Low-Level Sockets',
                'category' => 'Web Services & API',
                'package' => 'common',
                'description' => 'Low-level Berkeley socket interface for TCP/UDP network servers and daemons.',
                'is_core' => false,
                'is_recommended' => true,
            ],

            // XML & Data Processing
            'xml' => [
                'name' => 'xml',
                'title' => 'XML Core Parser',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'Standard event-driven Expat XML document parser.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'simplexml' => [
                'name' => 'simplexml',
                'title' => 'SimpleXML Tree Parser',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'Converts XML documents into intuitive object trees for easy navigation.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'xmlreader' => [
                'name' => 'xmlreader',
                'title' => 'XMLReader Pull Parser',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'Memory-efficient streaming pull parser for massive XML data feeds.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'xmlwriter' => [
                'name' => 'xmlwriter',
                'title' => 'XMLWriter Generator',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'Fast streaming XML document generator and serializer.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'dom' => [
                'name' => 'dom',
                'title' => 'DOM XML & HTML5 Parser',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'W3C compliant Document Object Model (DOM) tree manipulation and HTML parsing.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'xsl' => [
                'name' => 'xsl',
                'title' => 'XSLT Stylesheet Processor',
                'category' => 'XML & Data Processing',
                'package' => 'xml',
                'description' => 'Transforms XML documents into HTML, PDF, or other formats via XSL stylesheets.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'json' => [
                'name' => 'json',
                'title' => 'JSON Parser & Encoder',
                'category' => 'XML & Data Processing',
                'package' => 'common',
                'description' => 'High-speed native JavaScript Object Notation (JSON) serialization and decoding.',
                'is_core' => true,
                'is_recommended' => true,
            ],
            'yaml' => [
                'name' => 'yaml',
                'title' => 'YAML Data Parser',
                'category' => 'XML & Data Processing',
                'package' => 'yaml',
                'description' => 'Parses human-readable YAML configuration files into PHP arrays.',
                'is_core' => false,
                'is_recommended' => false,
            ],

            // Math & Internationalization
            'bcmath' => [
                'name' => 'bcmath',
                'title' => 'BCMath Arbitrary Precision',
                'category' => 'Math & Internationalization',
                'package' => 'bcmath',
                'description' => 'Arbitrary precision mathematics for e-commerce, banking, and crypto calculations.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'gmp' => [
                'name' => 'gmp',
                'title' => 'GMP Big Integer Math',
                'category' => 'Math & Internationalization',
                'package' => 'gmp',
                'description' => 'GNU Multiple Precision Arithmetic Library for cryptographic algorithms.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'intl' => [
                'name' => 'intl',
                'title' => 'Internationalization (ICU)',
                'category' => 'Math & Internationalization',
                'package' => 'intl',
                'description' => 'ICU collation, date/time/number formatting, transliteration, and currency parsing.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'mbstring' => [
                'name' => 'mbstring',
                'title' => 'Multibyte String (UTF-8)',
                'category' => 'Math & Internationalization',
                'package' => 'mbstring',
                'description' => 'Essential UTF-8, Unicode, and multibyte character encoding functions.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'iconv' => [
                'name' => 'iconv',
                'title' => 'Iconv Character Transcoding',
                'category' => 'Math & Internationalization',
                'package' => 'common',
                'description' => 'Converts strings between different character encoding sets.',
                'is_core' => true,
                'is_recommended' => true,
            ],

            // Security & Cryptography
            'openssl' => [
                'name' => 'openssl',
                'title' => 'OpenSSL Cryptography',
                'category' => 'Security & Cryptography',
                'package' => 'common',
                'description' => 'Symmetric/asymmetric encryption, hashing, X.509 certificate generation, and TLS.',
                'is_core' => true,
                'is_recommended' => true,
            ],
            'sodium' => [
                'name' => 'sodium',
                'title' => 'Sodium Modern Crypto',
                'category' => 'Security & Cryptography',
                'package' => 'common',
                'description' => 'State-of-the-art libsodium cryptographic library for password hashing and signing.',
                'is_core' => true,
                'is_recommended' => true,
            ],
            'hash' => [
                'name' => 'hash',
                'title' => 'Hash & HMAC Engines',
                'category' => 'Security & Cryptography',
                'package' => 'common',
                'description' => 'Direct Message Digest and HMAC algorithms (SHA-256, SHA-512, bcrypt).',
                'is_core' => true,
                'is_recommended' => true,
            ],

            // Performance & Debugging
            'opcache' => [
                'name' => 'opcache',
                'title' => 'Zend OPcache & JIT',
                'category' => 'Performance & Debugging',
                'package' => 'opcache',
                'description' => 'Precompiles PHP bytecode in shared memory and executes via Just-In-Time (JIT) compiler.',
                'is_core' => false,
                'is_recommended' => true,
            ],
            'apcu' => [
                'name' => 'apcu',
                'title' => 'APCu User Memory Cache',
                'category' => 'Performance & Debugging',
                'package' => 'apcu',
                'description' => 'In-memory key-value data caching directly in PHP process memory.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'igbinary' => [
                'name' => 'igbinary',
                'title' => 'Igbinary Fast Serializer',
                'category' => 'Performance & Debugging',
                'package' => 'igbinary',
                'description' => 'Drop-in binary serializer replacement for standard PHP serialize, saving RAM.',
                'is_core' => false,
                'is_recommended' => false,
            ],
            'xdebug' => [
                'name' => 'xdebug',
                'title' => 'Xdebug Profiler & Debugger',
                'category' => 'Performance & Debugging',
                'package' => 'xdebug',
                'description' => 'Step debugger and code execution profiler for development diagnostics.',
                'is_core' => false,
                'is_recommended' => false,
            ],
        ];
    }

    /**
     * Get the extension catalog for a specific PHP version with loaded status.
     */
    public function getCatalog(string $version): array
    {
        $definitions = $this->getExtensionDefinitions();
        $cliBinary = "/usr/bin/php{$version}";
        
        $loadedRaw = [];
        if (file_exists($cliBinary)) {
            $res = $this->executeCommand([$cliBinary, '-m']);
            if ($res['success']) {
                $lines = explode("\n", $res['output']);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed && !str_starts_with($trimmed, '[') && !str_starts_with($trimmed, ';')) {
                        $loadedRaw[] = strtolower($trimmed);
                    }
                }
            }
        }

        // Check mods-available and conf.d
        $modsDir = "/etc/php/{$version}/mods-available";
        $fpmConfDir = "/etc/php/{$version}/fpm/conf.d";
        $availableMods = is_dir($modsDir) ? scandir($modsDir) : [];
        $enabledMods = is_dir($fpmConfDir) ? scandir($fpmConfDir) : [];

        $catalog = [];

        foreach ($definitions as $key => $def) {
            $isLoaded = in_array(strtolower($key), $loadedRaw, true)
                || in_array(strtolower($def['name']), $loadedRaw, true)
                || ($key === 'opcache' && in_array('zend opcache', $loadedRaw, true));

            $iniFile = "{$key}.ini";
            $isModAvailable = in_array($iniFile, $availableMods, true)
                || in_array("{$def['package']}.ini", $availableMods, true);

            $catalog[$key] = array_merge($def, [
                'is_loaded' => $isLoaded,
                'is_available' => $isModAvailable || $def['is_core'] || $isLoaded,
            ]);
        }

        return $catalog;
    }

    /**
     * Get extension statistics for metric cards.
     */
    public function getStats(string $version): array
    {
        $catalog = $this->getCatalog($version);
        $totalCatalog = count($catalog);
        $loadedCount = count(array_filter($catalog, fn($e) => $e['is_loaded']));
        $coreCount = count(array_filter($catalog, fn($e) => $e['is_core']));
        $dynamicLoaded = count(array_filter($catalog, fn($e) => $e['is_loaded'] && !$e['is_core']));
        $opcacheLoaded = $catalog['opcache']['is_loaded'] ?? false;

        return [
            'total_catalog' => $totalCatalog,
            'loaded_count' => $loadedCount,
            'core_count' => $coreCount,
            'dynamic_loaded' => $dynamicLoaded,
            'opcache_loaded' => $opcacheLoaded,
        ];
    }

    /**
     * Toggle or install/remove an extension for a specific PHP version.
     */
    public function toggleExtension(string $version, string $extension, string $action, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $definitions = $this->getExtensionDefinitions();
        if (!isset($definitions[$extension])) {
            return ['success' => false, 'error' => "Unknown extension: {$extension}"];
        }

        $def = $definitions[$extension];
        if ($def['is_core'] && $action === 'disable') {
            return ['success' => false, 'error' => "Core compiled extension '{$extension}' cannot be disabled."];
        }

        $pkgName = $def['package'];
        $fullPackage = "php{$version}-{$pkgName}";

        if ($action === 'install' || $action === 'enable') {
            // First check if already on disk in mods-available
            $iniFile = "/etc/php/{$version}/mods-available/{$extension}.ini";
            $altIni = "/etc/php/{$version}/mods-available/{$pkgName}.ini";

            if (file_exists($iniFile) || file_exists($altIni)) {
                $target = file_exists($iniFile) ? $extension : $pkgName;
                $this->executeSudoCommand(['phpenmod', '-v', $version, $target]);
            } else {
                // Install deb package
                $cmd = [
                    'sudo',
                    'DEBIAN_FRONTEND=noninteractive',
                    'NEEDRESTART_MODE=l',
                    'apt-get', 'install', '-y', '-q',
                    '-o', 'Dpkg::Options::=--force-confdef',
                    '-o', 'Dpkg::Options::=--force-confold',
                    $fullPackage
                ];
                $res = $this->executeCommand($cmd);
                if (!$res['success']) {
                    return ['success' => false, 'error' => "Failed to install package {$fullPackage}: " . ($res['error'] ?? $res['error_output'])];
                }
            }
        } elseif ($action === 'remove' || $action === 'disable') {
            // Disable module
            $this->executeSudoCommand(['phpdismod', '-v', $version, $extension]);
            $this->executeSudoCommand(['phpdismod', '-v', $version, $pkgName]);
        }

        // Reload PHP-FPM
        $this->fpmService->manageService($version, 'reload');

        $this->logAction($adminId, "php_extension_{$action}", [
            'version' => $version,
            'extension' => $extension,
            'action' => $action,
        ]);

        return [
            'success' => true,
            'message' => "Extension '{$def['title']}' ({$extension}) successfully {$action}d for PHP {$version}.",
        ];
    }

    /**
     * Install the complete recommended hosting stack.
     */
    public function installRecommendedStack(string $version, ?int $adminId = null): array
    {
        if (!preg_match('/^[0-9]+\.[0-9]+$/', $version)) {
            return ['success' => false, 'error' => "Invalid PHP version: {$version}"];
        }

        $recommended = ['curl', 'gd', 'mbstring', 'mysql', 'xml', 'zip', 'bcmath', 'intl', 'soap', 'redis', 'imagick', 'opcache'];
        $packages = array_map(fn($r) => "php{$version}-{$r}", $recommended);

        $cmd = array_merge([
            'sudo',
            'DEBIAN_FRONTEND=noninteractive',
            'NEEDRESTART_MODE=l',
            'apt-get', 'install', '-y', '-q',
            '-o', 'Dpkg::Options::=--force-confdef',
            '-o', 'Dpkg::Options::=--force-confold',
        ], $packages);

        $res = $this->executeCommand($cmd);

        // Reload FPM
        $this->fpmService->manageService($version, 'reload');

        $this->logAction($adminId, 'php_extension_stack_installed', [
            'version' => $version,
            'packages' => $packages,
        ]);

        return [
            'success' => true,
            'message' => "Recommended high-performance extension bundle installed and PHP {$version}-FPM reloaded.",
        ];
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "PHP Extension Manager: {$action}",
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
