<?php
namespace App\Jobs;

use App\Models\Subscription;
use App\Services\DatabaseManager;
use App\Services\NginxManager;
use App\Services\SSLManager;
use App\Services\UserManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateHostingAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 600;
    public $tries = 3;
    
    protected Subscription $subscription;
    
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
    
    public function handle(
        UserManager $userManager,
        NginxManager $nginxManager,
        DatabaseManager $databaseManager,
        SSLManager $sslManager
    ): void {
        // 1. Create system user
        $userResult = $userManager->createSystemUser(
            $this->subscription->username
        );
        
        if (!$userResult['success']) {
            $this->fail(new \Exception('Failed to create system user: ' . $userResult['error']));
            return;
        }
        
        // 2. Create document root structure
        $basePath = $this->subscription->document_root;
        $this->createDirectoryStructure($basePath);
        
        // 3. Create Nginx virtual host
        $nginxResult = $nginxManager->createVirtualHost([
            'domain' => $this->subscription->domain,
            'username' => $this->subscription->username,
            'document_root' => $this->subscription->document_root,
            'php_version' => $this->subscription->php_version ?? '8.2',
            'ssl_enabled' => false
        ]);
        
        if (!$nginxResult['success']) {
            $this->fail(new \Exception('Failed to create Nginx config: ' . $nginxResult['error']));
            return;
        }
        
        // 4. Create database
        $dbName = 'db_' . $this->subscription->username;
        $dbUser = 'usr_' . $this->subscription->username;
        
        $dbResult = $databaseManager->createDatabase([
            'name' => $dbName,
            'db_user' => $dbUser
        ]);
        
        if ($dbResult['success']) {
            // Save database info
            $this->subscription->databases()->create([
                'name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbResult['db_password'],
                'host' => 'localhost'
            ]);
        }
        
        // 5. Generate SSL if auto-ssl is enabled
        if ($this->subscription->plan && $this->subscription->plan->auto_ssl) {
            $sslResult = $sslManager->generateSSL(
                $this->subscription->domain,
                $this->subscription->user->email
            );
            
            if ($sslResult['success']) {
                $nginxManager->createVirtualHost([
                    'domain' => $this->subscription->domain,
                    'username' => $this->subscription->username,
                    'document_root' => $this->subscription->document_root,
                    'php_version' => $this->subscription->php_version ?? '8.2',
                    'ssl_enabled' => true
                ]);
            }
        }
        
        // 6. Create default index page
        $this->createDefaultIndex($this->subscription->document_root);
        
        // 7. Update subscription status
        $this->subscription->update([
            'status' => 'active',
            'expires_at' => now()->addMonth()
        ]);
    }
    
    private function createDirectoryStructure(string $basePath): void
    {
        $directories = [
            $basePath,
            $basePath . '/public_html',
            $basePath . '/logs',
            $basePath . '/private',
            $basePath . '/tmp'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                @chown($dir, $this->subscription->username);
                @chgrp($dir, 'www-data');
            }
        }
    }
    
    private function createDefaultIndex(string $documentRoot): void
    {
        $domain = $this->subscription->domain;
        $templatePath = resource_path('views/templates/default_holding_page.html');
        if (file_exists($templatePath)) {
            $indexContent = str_replace('{{DOMAIN}}', $domain, file_get_contents($templatePath));
        } else {
            $indexContent = "<!DOCTYPE html><html><head><title>{$domain}</title></head><body><h1>Welcome to {$domain}</h1></body></html>";
        }
        
        $indexPath = $documentRoot . '/public_html/index.html';
        file_put_contents($indexPath, $indexContent);
        @chmod($indexPath, 0664);
        @chown($indexPath, $this->subscription->username);
        @chgrp($indexPath, 'www-data');
    }
}
