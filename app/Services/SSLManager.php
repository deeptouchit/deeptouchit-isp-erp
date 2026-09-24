<?php
namespace App\Services;

use App\Traits\CommandExecutor;

class SSLManager
{
    use CommandExecutor;
    
    public function generateSSL(string $domain, string $email, array $subdomains = []): array
    {
        $domains = [$domain];
        foreach ($subdomains as $subdomain) {
            $domains[] = $subdomain . '.' . $domain;
        }
        
        $domainArgs = '';
        foreach ($domains as $d) {
            $domainArgs .= " -d {$d}";
        }
        
        $command = "sudo certbot --nginx{$domainArgs} --non-interactive --agree-tos -m {$email} --redirect";
        
        $result = $this->executeCommand(explode(' ', $command));
        
        if ($result['success']) {
            // Extract certificate paths
            return [
                'success' => true,
                'cert_path' => "/etc/letsencrypt/live/{$domain}/fullchain.pem",
                'key_path' => "/etc/letsencrypt/live/{$domain}/privkey.pem",
                'output' => $result['output']
            ];
        }
        
        return ['success' => false, 'error' => $result['error_output'] ?? ($result['error'] ?? 'SSL generation failed')];
    }
    
    public function renewSSL(): array
    {
        return $this->executeCommand(['sudo', 'certbot', 'renew', '--quiet', '--no-self-upgrade']);
    }
    
    public function revokeSSL(string $domain): array
    {
        return $this->executeCommand(['sudo', 'certbot', 'revoke', '--cert-name', $domain, '--non-interactive']);
    }
}
