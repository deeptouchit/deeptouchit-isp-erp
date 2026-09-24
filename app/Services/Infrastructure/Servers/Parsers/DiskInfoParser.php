<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class DiskInfoParser
{
    public function parse(string $rawOutput): array
    {
        $totalGb = 0;
        $usedGb = 0;
        $usagePercent = 0.0;

        $lines = explode("\n", trim($rawOutput));
        // Expecting output of df -PB1 /
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, 'Filesystem')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 5) {
                $totalBytes = (float) $parts[1];
                $usedBytes = (float) $parts[2];
                $pctStr = rtrim($parts[4], '%');

                $totalGb = (int) round($totalBytes / (1024 * 1024 * 1024));
                $usedGb = (int) round($usedBytes / (1024 * 1024 * 1024));
                $usagePercent = (float) $pctStr;
                break;
            }
        }

        return [
            'total_disk' => $totalGb,
            'used_disk' => $usedGb,
            'disk_usage' => $usagePercent,
        ];
    }
}
