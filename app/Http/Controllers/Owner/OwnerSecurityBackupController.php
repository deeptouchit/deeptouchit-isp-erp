<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use App\Models\Setting;
use App\Services\Backup\OffsiteBackupService;
use App\Services\Backup\RestoreService;
use App\Services\Security\SecurityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class OwnerSecurityBackupController extends Controller
{
    public function __construct(
        protected SecurityLogService $securityLogService,
        protected OffsiteBackupService $offsiteBackupService,
        protected RestoreService $restoreService
    ) {}

    public function index(Request $request)
    {
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();

        // Ensure backup directories exist
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        // List existing backup files
        $backupFiles = [];
        $files = File::files($backupDir);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            $backupFiles[] = [
                'filename' => $filename,
                'size' => $this->formatSizeUnits($file->getSize()),
                'bytes' => $file->getSize(),
                'created_at' => date('d M Y, h:i A', $file->getMTime()),
                'type' => str_contains($filename, 'full') ? 'Full System' : 'Database SQL',
                'sha256' => substr(hash_file('sha256', $file->getPathname()), 0, 16) . '...',
            ];
        }

        // Sort latest backups first
        usort($backupFiles, function ($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });

        // Pull Real Security Audit Logs with Filtering
        $severityFilter = $request->get('severity');
        $eventFilter = $request->get('event_type');

        $query = SecurityAuditLog::latest();
        if ($severityFilter) {
            $query->where('severity', $severityFilter);
        }
        if ($eventFilter) {
            $query->where('event_type', 'like', "%{$eventFilter}%");
        }

        $auditLogs = $query->paginate(15);

        // Calculate Security Posture Stats
        $totalLogsCount = SecurityAuditLog::count();
        $warningCount = SecurityAuditLog::where('severity', 'warning')->count();
        $criticalCount = SecurityAuditLog::whereIn('severity', ['critical', 'alert'])->count();
        $blockedCount = SecurityAuditLog::where('status', 'blocked')->count();

        // Offsite status
        $offsiteEnabled = ($settings['offsite_backup_enabled'] ?? '0') === '1';

        return view('owner.security-backup.index', compact(
            'settings',
            'backupFiles',
            'auditLogs',
            'totalLogsCount',
            'warningCount',
            'criticalCount',
            'blockedCount',
            'offsiteEnabled'
        ));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token']);

        foreach ($inputs as $key => $value) {
            Setting::set($key, (string)$value, 'security', null);
        }

        $this->securityLogService->log(
            eventType: 'security_settings_updated',
            description: 'Platform Owner updated system security policies and backup configurations.',
            severity: 'info',
            details: array_keys($inputs)
        );

        return back()->with('success', 'Security policies and backup settings saved successfully.');
    }

    /**
     * Trigger and generate a full real database SQL backup with integrity verification.
     */
    public function triggerBackup(?Request $request = null)
    {
        $request = $request ?? request();
        $type = $request->input('backup_type', 'database');
        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "somitysoft_{$type}_backup_{$timestamp}.sql";
        $filePath = $backupDir . '/' . $filename;

        try {
            $handle = fopen($filePath, 'w+');
            if (!$handle) {
                return back()->with('error', 'Unable to create backup target file on disk.');
            }

            $driver = DB::getDriverName();
            $dbName = config('database.connections.' . config('database.default') . '.database', 'somitysoft_db');

            // Write Standard Backup Header
            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- SomitySoft SaaS Enterprise Automated Database Dump\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: {$dbName} (Driver: {$driver})\n");
            fwrite($handle, "-- ========================================================\n\n");

            if ($driver === 'mysql') {
                fwrite($handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
                fwrite($handle, "/*!40101 SET NAMES utf8mb4 */;\n");
                fwrite($handle, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
                fwrite($handle, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n\n");

                $tables = DB::select('SHOW TABLES');
                $dbProp = 'Tables_in_' . $dbName;

                foreach ($tables as $tableRow) {
                    $tableName = $tableRow->$dbProp ?? array_values((array)$tableRow)[0];
                    
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "-- Table structure for table `{$tableName}`\n");
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                    $createTableRes = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    $createSql = $createTableRes[0]->{'Create Table'} ?? null;
                    if ($createSql) {
                        fwrite($handle, $createSql . ";\n\n");
                    }

                    // Table Data
                    $rows = DB::table($tableName)->get();
                    if ($rows->count() > 0) {
                        fwrite($handle, "-- Dumping data for table `{$tableName}`\n");
                        foreach ($rows->chunk(100) as $chunk) {
                            foreach ($chunk as $row) {
                                $rowArray = (array)$row;
                                $columns = array_keys($rowArray);
                                $escapedValues = array_map(function ($val) {
                                    if (is_null($val)) return "NULL";
                                    return "'" . addslashes((string)$val) . "'";
                                }, array_values($rowArray));

                                $colNames = implode('`, `', $columns);
                                $valString = implode(', ', $escapedValues);
                                fwrite($handle, "INSERT INTO `{$tableName}` (`{$colNames}`) VALUES ({$valString});\n");
                            }
                        }
                        fwrite($handle, "\n");
                    }
                }

                fwrite($handle, "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
                fwrite($handle, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
            } else {
                // SQLite Driver fallback for testing
                $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($tables as $t) {
                    fwrite($handle, "DROP TABLE IF EXISTS \"{$t->name}\";\n");
                    fwrite($handle, "{$t->sql};\n\n");

                    $rows = DB::table($t->name)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array)$row;
                        $columns = array_keys($rowArray);
                        $escapedValues = array_map(function ($val) {
                            if (is_null($val)) return "NULL";
                            return "'" . addslashes((string)$val) . "'";
                        }, array_values($rowArray));

                        $colNames = implode('", "', $columns);
                        $valString = implode(', ', $escapedValues);
                        fwrite($handle, "INSERT INTO \"{$t->name}\" (\"{$colNames}\") VALUES ({$valString});\n");
                    }
                    fwrite($handle, "\n");
                }
            }

            fwrite($handle, "-- Dump completed successfully.\n");
            fclose($handle);

            // BACKUP INTEGRITY VERIFICATION
            $fileSize = File::size($filePath);
            if ($fileSize < 500) {
                File::delete($filePath);
                return back()->with('error', 'Backup verification failed: generated file is empty.');
            }

            $checksum = hash_file('sha256', $filePath);

            // Log security audit event
            $this->securityLogService->logBackupEvent($filename, 'created', [
                'size' => $fileSize,
                'sha256' => $checksum,
            ]);

            // Auto-sync offsite if enabled
            if (Setting::get('offsite_backup_enabled', '0') === '1') {
                $this->offsiteBackupService->uploadToOffsite($filePath);
            }

            return back()->with('success', "Database backup '{$filename}' ({$this->formatSizeUnits($fileSize)}) created and verified successfully. SHA-256: " . substr($checksum, 0, 12) . '...');
        } catch (\Throwable $e) {
            Log::error('Database Backup Generation Failed: ' . $e->getMessage());
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform non-destructive sandbox dry-run restore validation.
     */
    public function testRestore(Request $request, $filename)
    {
        $filePath = storage_path('app/backups/' . basename($filename));

        if (!File::exists($filePath)) {
            return back()->with('error', 'Target backup file not found.');
        }

        $result = $this->restoreService->testRestore($filePath);

        if ($result['passed']) {
            return back()->with('success', "✅ [Integrity PASSED] {$result['filename']}: Validated {$result['table_count']} tables & {$result['insert_statements']} queries in {$result['duration_ms']}ms. Ready for instant recovery.");
        } else {
            return back()->with('error', "❌ [Integrity FAILED] {$result['filename']}: " . implode(', ', $result['errors'] ?? [$result['message']]));
        }
    }

    /**
     * Manually trigger offsite cloud upload for a specific backup.
     */
    public function uploadOffsite(Request $request, $filename)
    {
        $filePath = storage_path('app/backups/' . basename($filename));

        if (!File::exists($filePath)) {
            return back()->with('error', 'Target backup file not found.');
        }

        $result = $this->offsiteBackupService->uploadToOffsite($filePath);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    public function downloadBackup($filename)
    {
        $filePath = storage_path('app/backups/' . basename($filename));

        if (!File::exists($filePath)) {
            return back()->with('error', 'Backup file could not be found.');
        }

        return response()->download($filePath);
    }

    public function deleteBackup(Request $request, $filename)
    {
        $filePath = storage_path('app/backups/' . basename($filename));

        if (File::exists($filePath)) {
            File::delete($filePath);
            $this->securityLogService->logBackupEvent(basename($filename), 'deleted');
            return back()->with('success', 'Backup snapshot removed successfully.');
        }

        return back()->with('error', 'Unable to delete the backup file.');
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }
}
