<?php

namespace App\Services;

use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Storage;

class BackupManager
{
    use CommandExecutor;

    /**
     * Create full backup archive for a subscription.
     */
    public function createSubscriptionBackup(Subscription $subscription): array
    {
        $backupDir = config('panel.hosting.backup_path', '/var/www/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_' . $subscription->username . '_' . date('Y-m-d_His') . '.tar.gz';
        $fullPath = $backupDir . '/' . $filename;
        $sourcePath = "/var/www/vhosts/" . $subscription->username;

        if (!is_dir($sourcePath)) {
            mkdir($sourcePath, 0755, true);
        }

        $cmd = ['tar', '-czf', $fullPath, '-C', '/var/www/vhosts', $subscription->username];
        $result = $this->executeSudoCommand($cmd);

        return [
            'success' => $result['success'],
            'filename' => $filename,
            'file_path' => $fullPath,
            'size_bytes' => file_exists($fullPath) ? filesize($fullPath) : 0,
        ];
    }
}
