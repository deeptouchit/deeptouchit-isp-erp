<?php

namespace App\Services;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class FTPManager
{
    use CommandExecutor;

    /**
     * Create or update a virtual/system FTP user locked to a specific directory in vsftpd.
     */
    public function createFtpUser(string $username, string $password, string $directory, string $permissions = 'readwrite'): array
    {
        // 1. Ensure home directory exists and owned by www-data
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }
        @chown($directory, 'www-data');

        // 2. Ensure /etc/vsftpd_user_conf directory exists
        if (!is_dir('/etc/vsftpd_user_conf')) {
            $this->executeSudoCommand(['mkdir', '-p', '/etc/vsftpd_user_conf']);
        }

        // 3. Create or update Linux system user with /bin/false shell and www-data group
        $exists = function_exists('posix_getpwnam') ? (bool) posix_getpwnam($username) : false;
        if (!$exists) {
            $this->executeSudoCommand([
                'useradd',
                '-M',
                '-d', $directory,
                '-s', '/bin/false',
                '-G', 'www-data',
                $username
            ]);
        } else {
            $this->executeSudoCommand(['usermod', '-d', $directory, '-s', '/bin/false', '-G', 'www-data', $username]);
        }

        // 4. Update Password using chpasswd
        $passProcess = new Process(['sudo', 'chpasswd']);
        $passProcess->setInput("{$username}:{$password}");
        $passProcess->run();

        // 5. Add to /etc/vsftpd.userlist
        $userList = @file_get_contents('/etc/vsftpd.userlist') ?: '';
        $users = array_filter(array_map('trim', explode("\n", $userList)));
        if (!in_array($username, $users)) {
            $users[] = $username;
            $tmp = tempnam(sys_get_temp_dir(), 'vsuser_');
            file_put_contents($tmp, implode("\n", $users) . "\n");
            $this->executeSudoCommand(['cp', $tmp, '/etc/vsftpd.userlist']);
            $this->executeSudoCommand(['chmod', '644', '/etc/vsftpd.userlist']);
            @unlink($tmp);
        }

        // 6. Write per-user chroot jail configuration
        $writeEnable = $permissions === 'readonly' ? 'NO' : 'YES';
        $userConf = "local_root={$directory}\nwrite_enable={$writeEnable}\n";
        $tmpConf = tempnam(sys_get_temp_dir(), 'vsconf_');
        file_put_contents($tmpConf, $userConf);
        $this->executeSudoCommand(['cp', $tmpConf, "/etc/vsftpd_user_conf/{$username}"]);
        $this->executeSudoCommand(['chmod', '644', "/etc/vsftpd_user_conf/{$username}"]);
        @unlink($tmpConf);

        return [
            'success' => true,
            'username' => $username,
            'directory' => $directory,
            'host' => '103.59.177.138',
            'port' => 21
        ];
    }

    /**
     * Delete an FTP user from system and vsftpd.
     */
    public function deleteFtpUser(string $username): bool
    {
        // Remove from userlist
        $userList = @file_get_contents('/etc/vsftpd.userlist') ?: '';
        $users = array_filter(array_map('trim', explode("\n", $userList)));
        $users = array_diff($users, [$username]);
        
        $tmp = tempnam(sys_get_temp_dir(), 'vsuser_');
        file_put_contents($tmp, implode("\n", $users) . "\n");
        $this->executeSudoCommand(['cp', $tmp, '/etc/vsftpd.userlist']);
        $this->executeSudoCommand(['chmod', '644', '/etc/vsftpd.userlist']);
        @unlink($tmp);

        // Remove user conf
        $this->executeSudoCommand(['rm', '-f', "/etc/vsftpd_user_conf/{$username}"]);

        // Delete Linux system user
        $this->executeSudoCommand(['userdel', '-f', $username]);

        return true;
    }

    /**
     * Change FTP user password.
     */
    public function changePassword(string $username, string $newPassword): bool
    {
        $passProcess = new Process(['sudo', 'chpasswd']);
        $passProcess->setInput("{$username}:{$newPassword}");
        $passProcess->run();

        return $passProcess->isSuccessful();
    }

    /**
     * Toggle FTP user active/suspended in vsftpd.
     */
    public function toggleUserStatus(string $username, string $status): bool
    {
        $userList = @file_get_contents('/etc/vsftpd.userlist') ?: '';
        $users = array_filter(array_map('trim', explode("\n", $userList)));

        if ($status === 'active' && !in_array($username, $users)) {
            $users[] = $username;
        } elseif ($status === 'suspended') {
            $users = array_diff($users, [$username]);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'vsuser_');
        file_put_contents($tmp, implode("\n", $users) . "\n");
        $this->executeSudoCommand(['cp', $tmp, '/etc/vsftpd.userlist']);
        $this->executeSudoCommand(['chmod', '644', '/etc/vsftpd.userlist']);
        @unlink($tmp);

        return true;
    }

    /**
     * Update FTP daemon port and passive mode configuration.
     */
    public function updateFtpConfig(int $port, int $pasvMin = 30000, int $pasvMax = 31000, bool $updateFirewall = true, bool $restartService = true): array
    {
        $confPath = '/etc/vsftpd.conf';
        $content = '';
        if (file_exists($confPath) && is_readable($confPath)) {
            $content = file_get_contents($confPath);
        }

        if (preg_match('/^\s*listen_port\s*=.*/m', $content)) {
            $content = preg_replace('/^\s*listen_port\s*=.*/m', "listen_port={$port}", $content);
        } else {
            $content .= "\nlisten_port={$port}\n";
        }

        if (preg_match('/^\s*pasv_min_port\s*=.*/m', $content)) {
            $content = preg_replace('/^\s*pasv_min_port\s*=.*/m', "pasv_min_port={$pasvMin}", $content);
        } else {
            $content .= "pasv_min_port={$pasvMin}\n";
        }

        if (preg_match('/^\s*pasv_max_port\s*=.*/m', $content)) {
            $content = preg_replace('/^\s*pasv_max_port\s*=.*/m', "pasv_max_port={$pasvMax}", $content);
        } else {
            $content .= "pasv_max_port={$pasvMax}\n";
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'vsftpd_');
        file_put_contents($tmpFile, $content);

        $this->executeSudoCommand(['cp', $tmpFile, $confPath]);
        $this->executeSudoCommand(['chmod', '644', $confPath]);
        @unlink($tmpFile);

        if ($updateFirewall) {
            $this->executeSudoCommand(['ufw', 'allow', "{$port}/tcp"]);
            $this->executeSudoCommand(['ufw', 'allow', "{$pasvMin}:{$pasvMax}/tcp"]);
        }

        if ($restartService) {
            $this->executeSudoCommand(['systemctl', 'restart', 'vsftpd']);
        }

        return [
            'success' => true,
            'port' => $port,
            'pasv_min' => $pasvMin,
            'pasv_max' => $pasvMax,
        ];
    }
}
