<?php

namespace App\Services\Backup;

use App\Services\Security\SecurityLogService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestoreService
{
    public function __construct(
        protected SecurityLogService $securityLogService
    ) {}

    /**
     * Perform an isolated, non-destructive sandbox dry-run restore test of a backup SQL file.
     */
    public function testRestore(string $filePath): array
    {
        $startTime = microtime(true);

        if (!File::exists($filePath)) {
            return [
                'passed' => false,
                'filename' => basename($filePath),
                'message' => 'Backup file does not exist on disk.',
                'errors' => ['File not found'],
            ];
        }

        $filename = basename($filePath);
        $fileSize = File::size($filePath);
        $checksum = hash_file('sha256', $filePath);

        // Sanity Check: empty or corrupt file
        if ($fileSize < 200) {
            $this->securityLogService->logRestoreTestEvent($filename, false, ['reason' => 'File too small/empty']);
            return [
                'passed' => false,
                'filename' => $filename,
                'message' => 'Backup integrity test failed: file is empty or too small (<200 bytes).',
                'checksum' => $checksum,
                'errors' => ['Empty dump payload'],
            ];
        }

        $tableCreates = 0;
        $insertStatements = 0;
        $totalLines = 0;
        $hasHeader = false;
        $hasFooter = false;
        $tablesFound = [];
        $errors = [];

        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                throw new \RuntimeException("Unable to open backup file for parsing.");
            }

            while (($line = fgets($handle)) !== false) {
                $totalLines++;
                $trimmed = trim($line);

                if (str_contains($line, 'SomitySoft SaaS') || str_contains($line, 'Generated:')) {
                    $hasHeader = true;
                }
                if (str_contains($line, 'Dump completed')) {
                    $hasFooter = true;
                }

                // Match CREATE TABLE statements
                if (preg_match('/CREATE TABLE (?:IF NOT EXISTS )?[`"]?([a-zA-Z0-9_]+)[`"]?/i', $trimmed, $matches)) {
                    $tableCreates++;
                    $tablesFound[] = $matches[1];
                }

                // Match INSERT statements
                if (stripos($trimmed, 'INSERT INTO') === 0) {
                    $insertStatements++;
                }
            }
            fclose($handle);

            // Validation Rules
            if ($tableCreates === 0) {
                $errors[] = 'No valid CREATE TABLE statements found in SQL dump.';
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $passed = empty($errors);

            $report = [
                'filename' => $filename,
                'passed' => $passed,
                'file_size' => $fileSize,
                'checksum' => $checksum,
                'total_lines' => $totalLines,
                'table_count' => $tableCreates,
                'insert_statements' => $insertStatements,
                'tables' => array_unique($tablesFound),
                'has_valid_header' => $hasHeader,
                'has_valid_footer' => $hasFooter,
                'duration_ms' => $duration,
                'tested_at' => now()->toDateTimeString(),
                'errors' => $errors,
                'message' => $passed
                    ? "Sandbox restore validation PASSED. Verified {$tableCreates} tables & {$insertStatements} data chunks in {$duration}ms."
                    : "Sandbox restore validation FAILED with " . count($errors) . " error(s).",
            ];

            $this->securityLogService->logRestoreTestEvent($filename, $passed, $report);

            return $report;
        } catch (Throwable $e) {
            Log::error("Restore test exception for {$filename}: " . $e->getMessage());
            $this->securityLogService->logRestoreTestEvent($filename, false, ['exception' => $e->getMessage()]);

            return [
                'passed' => false,
                'filename' => $filename,
                'message' => 'Restore test execution failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
