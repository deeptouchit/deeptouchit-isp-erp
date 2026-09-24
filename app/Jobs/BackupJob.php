<?php
namespace App\Jobs;

use App\Models\BackupJob as BackupJobModel;
use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CommandExecutor;

    public $timeout = 3600;
    public $tries = 2;

    protected Subscription $subscription;
    protected string $type;

    public function __construct(Subscription $subscription, string $type = 'full')
    {
        $this->subscription = $subscription;
        $this->type = $type;
    }

    public function handle(): void
    {
        $backupDir = config('panel.hosting.backup_path', '/var/www/backups') . '/' . $this->subscription->username;
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0750, true, true);
        }

        $timestamp = now()->format('Ymd_His');
        $fileName = "backup_{$this->subscription->username}_{$this->type}_{$timestamp}.tar.gz";
        $filePath = "{$backupDir}/{$fileName}";

        $backupRecord = $this->subscription->backupJobs()->create([
            'type' => $this->type,
            'status' => 'running',
            'file_path' => $filePath,
            'started_at' => now(),
        ]);

        try {
            $docRoot = $this->subscription->document_root;
            $command = ['tar', '-czf', $filePath, '-C', dirname($docRoot), basename($docRoot)];

            $result = $this->executeCommand($command);

            if ($result['success'] && File::exists($filePath)) {
                $backupRecord->update([
                    'status' => 'completed',
                    'file_size' => filesize($filePath),
                    'completed_at' => now(),
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Backup archive failed.');
            }
        } catch (\Exception $e) {
            Log::error('Backup job failed: ' . $e->getMessage());
            $backupRecord->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            $this->fail($e);
        }
    }
}
