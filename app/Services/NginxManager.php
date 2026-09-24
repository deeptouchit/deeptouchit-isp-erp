<?php
namespace App\Services;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NginxManager
{
    use CommandExecutor;
    
    protected string $sitesAvailable = '/etc/nginx/sites-available';
    protected string $sitesEnabled = '/etc/nginx/sites-enabled';
    protected string $nginxConfigTemplate;
    
    public function __construct()
    {
        $this->nginxConfigTemplate = Storage::disk('local')->get('stubs/nginx.conf.stub') ?? "server {\n    listen 80;\n    server_name {DOMAIN};\n    root {DOCUMENT_ROOT};\n    index index.php index.html;\n    location ~ \\.php$ {\n        include snippets/fastcgi-php.conf;\n        fastcgi_pass unix:/run/php/php{PHP_VERSION}-fpm.sock;\n    }\n}\n";
    }
    
    public function createVirtualHost(array $data): array
    {
        $domain = $data['domain'];
        $username = $data['username'];
        $documentRoot = $data['document_root'];
        $phpVersion = $data['php_version'] ?? '8.2';
        $sslEnabled = $data['ssl_enabled'] ?? false;
        $subdomains = $data['subdomains'] ?? [];
        
        // Input Validation
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return ['success' => false, 'error' => 'Invalid domain name'];
        }
        
        if (!preg_match('/^[a-z][a-z0-9_]{2,31}$/', $username)) {
            return ['success' => false, 'error' => 'Invalid username format'];
        }
        
        $allowedVersions = ['8.1', '8.2', '8.3', '8.4', '8.5'];
        if (!in_array($phpVersion, $allowedVersions)) {
            return ['success' => false, 'error' => 'Invalid PHP version'];
        }
        
        $config = $this->generateConfig($domain, $username, $documentRoot, $phpVersion, $sslEnabled, $subdomains);
        
        $configPath = $this->sitesAvailable . '/' . $domain . '.conf';
        $tempPath = storage_path("app/temp_vhost_{$domain}.conf");
        file_put_contents($tempPath, $config);
        $this->executeSudoCommand(['cp', $tempPath, $configPath]);
        @unlink($tempPath);
        
        $linkPath = $this->sitesEnabled . '/' . $domain . '.conf';
        if (!file_exists($linkPath)) {
            $this->executeSudoCommand(['ln', '-sf', $configPath, $linkPath]);
        }
        
        $result = $this->executeSudoCommand(['nginx', '-t']);
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Nginx configuration failed: ' . ($result['error_output'] ?? $result['error'])];
        }
        
        $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
        
        return ['success' => true, 'config_path' => $configPath];
    }
    
    public function suspendDomain(string $domain): bool
    {
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return false;
        }
        
        $result = $this->executeSudoCommand(['rm', '-f', $this->sitesEnabled . '/' . $domain . '.conf']);
        if ($result['success']) {
            $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
            return true;
        }
        return false;
    }
    
    public function unsuspendDomain(string $domain): bool
    {
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return false;
        }
        
        $availablePath = $this->sitesAvailable . '/' . $domain . '.conf';
        $enabledPath = $this->sitesEnabled . '/' . $domain . '.conf';
        
        if (!file_exists($availablePath)) {
            return false;
        }
        
        $this->executeSudoCommand(['ln', '-sf', $availablePath, $enabledPath]);
        $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
        return true;
    }
    
    public function deleteDomain(string $domain): bool
    {
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return false;
        }
        
        $this->executeSudoCommand(['rm', '-f', $this->sitesAvailable . '/' . $domain . '.conf']);
        $this->executeSudoCommand(['rm', '-f', $this->sitesEnabled . '/' . $domain . '.conf']);
        $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
        return true;
    }
    
    public function updateDocumentRoot(string $domain, string $newDocumentRoot): array
    {
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain)) {
            return ['success' => false, 'error' => 'Invalid domain name'];
        }

        $configPath = $this->sitesAvailable . '/' . $domain . '.conf';
        if (!file_exists($configPath)) {
            return ['success' => false, 'error' => "Nginx configuration not found for {$domain}"];
        }

        // Replace all root directives in the virtualhost config
        $result = $this->executeSudoCommand(['sed', '-i', "s|root .*;|root {$newDocumentRoot};|g", $configPath]);
        if (!$result['success']) {
            return ['success' => false, 'error' => 'Failed to update Nginx configuration file'];
        }

        $testResult = $this->executeSudoCommand(['nginx', '-t']);
        if (!$testResult['success']) {
            return ['success' => false, 'error' => 'Nginx configuration test failed: ' . ($testResult['error_output'] ?? $testResult['error'])];
        }

        $this->executeSudoCommand(['systemctl', 'reload', 'nginx']);
        return ['success' => true];
    }

    protected function generateConfig(string $domain, string $username, string $documentRoot, string $phpVersion, bool $ssl, array $subdomains): string
    {
        $logDir = "/var/www/vhosts/{$username}/logs";
        if (!is_dir($logDir)) {
            $this->executeSudoCommand(['mkdir', '-p', $logDir]);
            $this->executeSudoCommand(['chown', '-R', 'www-data:www-data', "/var/www/vhosts/{$username}"]);
        }

        $userSocket = "/run/php/php{$phpVersion}-fpm-{$username}.sock";
        $defaultSocket = "/run/php/php{$phpVersion}-fpm.sock";
        $socketPath = file_exists($userSocket) ? $userSocket : $defaultSocket;
        
        $sslConfig = '';
        if ($ssl) {
            $sslConfig = "
                listen 443 ssl http2;
                ssl_certificate /etc/nginx/ssl/{$domain}.crt;
                ssl_certificate_key /etc/nginx/ssl/{$domain}.key;
                ssl_protocols TLSv1.2 TLSv1.3;
                ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
                ssl_prefer_server_ciphers off;
                ssl_session_cache shared:SSL:10m;
                ssl_session_timeout 1d;
                ssl_session_tickets off;
            ";
        }
        
        $subdomainConfigs = '';
        foreach ($subdomains as $subdomain) {
            if (preg_match('/^[a-zA-Z0-9.-]+$/', $subdomain)) {
                $subdomainConfigs .= "
                    server_name {$subdomain}.{$domain};
                    root {$documentRoot};
                    index index.php index.html;
                    
                    location ~ \.php$ {
                        include snippets/fastcgi-php.conf;
                        fastcgi_pass unix:{$socketPath};
                    }
                ";
            }
        }
        
        $isSubdomain = substr_count($domain, '.') > 1;
        $serverNames = $isSubdomain ? $domain : "{$domain} www.{$domain}";

        return "
            server {
                listen 80;
                server_name {$serverNames};
                root {$documentRoot};
                index index.php index.html;
                
                {$sslConfig}
                
                location / {
                    try_files \$uri \$uri/ /index.php?\$query_string;
                }
                
                location ~ \.php$ {
                    include snippets/fastcgi-php.conf;
                    fastcgi_pass unix:{$socketPath};
                }
                
                location ~ /\.(?!well-known).* {
                    deny all;
                }
                
                {$subdomainConfigs}
                
                # Security Headers
                add_header X-Frame-Options \"SAMEORIGIN\" always;
                add_header X-XSS-Protection \"1; mode=block\" always;
                add_header X-Content-Type-Options \"nosniff\" always;
                add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;
                add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains\" always;
                
                # Logs
                access_log {$logDir}/access.log;
                error_log {$logDir}/error.log;
            }
        ";
    }
}
