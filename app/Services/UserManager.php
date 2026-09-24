<?php
namespace App\Services;

use App\Traits\CommandExecutor;

class UserManager
{
    use CommandExecutor;
    
    public function createSystemUser(string $username, string $homeDir = '/var/www/vhosts'): array
    {
        // Check if user exists
        $check = $this->executeCommand(['id', $username]);
        if ($check['success']) {
            return ['success' => false, 'error' => 'User already exists'];
        }
        
        $homePath = $homeDir . '/' . $username;
        
        // Create user
        $result = $this->executeSudoCommand([
            'adduser', '--disabled-login', '--gecos', '""', 
            '--home', $homePath, $username
        ]);
        
        if (!$result['success']) {
            return ['success' => false, 'error' => $result['error_output'] ?? ($result['error'] ?? 'User creation failed')];
        }
        
        // Set permissions
        $this->executeSudoCommand(['chown', $username . ':www-data', $homePath]);
        $this->executeSudoCommand(['chmod', '750', $homePath]);
        
        return ['success' => true, 'home_path' => $homePath];
    }
    
    public function deleteSystemUser(string $username): bool
    {
        $result = $this->executeSudoCommand(['userdel', '-r', $username]);
        return $result['success'];
    }
    
    public function suspendSystemUser(string $username): bool
    {
        $result = $this->executeSudoCommand(['usermod', '-L', $username]);
        return $result['success'];
    }
    
    public function unsuspendSystemUser(string $username): bool
    {
        $result = $this->executeSudoCommand(['usermod', '-U', $username]);
        return $result['success'];
    }
}
