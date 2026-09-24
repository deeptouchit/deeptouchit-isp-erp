<?php

namespace App\Console\Commands;

use App\Services\Backup\RestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestBackupRestoreCommand extends Command
{
    protected $signature = 'backup:test-restore {file? : Optional filename in storage/app/backups}';
    protected $description = 'Perform sandbox disaster recovery dry-run restore validation on SQL backup archives';

    public function handle(RestoreService $restoreService): int
    {
        $this->info("==================================================================");
        $this->info(" SomitySoft SaaS Disaster Recovery & Restore Sandbox Validator    ");
        $this->info("==================================================================");

        $backupDir = storage_path('app/backups');
        $fileName = $this->argument('file');

        if ($fileName) {
            $filePath = $backupDir . '/' . basename($fileName);
        } else {
            // Find most recent backup
            $files = File::files($backupDir);
            if (empty($files)) {
                $this->error("No backup files found in {$backupDir}. Take a snapshot first.");
                return 1;
            }

            usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
            $filePath = $files[0]->getPathname();
        }

        $this->line("Target Backup File: <comment>" . basename($filePath) . "</comment>");
        $this->line("Starting parsing, checksum calculation, and sandbox dry-run integrity verification...");

        $result = $restoreService->testRestore($filePath);

        if ($result['passed']) {
            $this->info("\n✅ RESTORE VERIFICATION PASSED (100% HEALTHY)");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Filename', $result['filename']],
                    ['SHA-256 Checksum', substr($result['checksum'], 0, 24) . '...'],
                    ['File Size', number_format($result['file_size'] / 1024, 2) . ' KB'],
                    ['Tables Verified', $result['table_count']],
                    ['Insert Chunks', $result['insert_statements']],
                    ['Execution Duration', $result['duration_ms'] . ' ms'],
                    ['Status', 'READY FOR IMMEDIATE RECOVERY'],
                ]
            );
            return 0;
        } else {
            $this->error("\n❌ RESTORE VERIFICATION FAILED");
            $this->line("<fg=red>" . $result['message'] . "</>");
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $err) {
                    $this->error(" - " . $err);
                }
            }
            return 1;
        }
    }
}
