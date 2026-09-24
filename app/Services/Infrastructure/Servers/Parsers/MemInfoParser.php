<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class MemInfoParser
{
    public function parse(string $rawOutput): array
    {
        $memTotalKb = 0;
        $memAvailableKb = 0;
        $memFreeKb = 0;

        $lines = explode("\n", $rawOutput);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || !str_contains($line, ':')) {
                continue;
            }

            [$key, $val] = explode(':', $line, 2);
            $key = trim($key);
            $val = (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);

            match ($key) {
                'MemTotal' => $memTotalKb = $val,
                'MemAvailable' => $memAvailableKb = $val,
                'MemFree' => $memFreeKb = $val,
                default => null,
            };
        }

        if ($memAvailableKb === 0) {
            $memAvailableKb = $memFreeKb;
        }

        $totalMb = (int) round($memTotalKb / 1024);
        $availMb = (int) round($memAvailableKb / 1024);
        $usedMb = max(0, $totalMb - $availMb);

        return [
            'total_ram' => $totalMb,
            'used_ram' => $usedMb,
            'available_ram' => $availMb,
        ];
    }
}
