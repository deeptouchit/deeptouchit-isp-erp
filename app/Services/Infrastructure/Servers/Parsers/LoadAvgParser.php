<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class LoadAvgParser
{
    public function parse(string $rawOutput): array
    {
        $load1 = 0.0;
        $load5 = 0.0;
        $load15 = 0.0;
        $uptimeSec = 0;

        $lines = explode("\n", trim($rawOutput));
        if (isset($lines[0])) {
            $parts = preg_split('/\s+/', trim($lines[0]));
            if (count($parts) >= 3) {
                $load1 = (float) $parts[0];
                $load5 = (float) $parts[1];
                $load15 = (float) $parts[2];
            }
        }

        if (isset($lines[1])) {
            $parts = preg_split('/\s+/', trim($lines[1]));
            if (isset($parts[0])) {
                $uptimeSec = (int) (float) $parts[0];
            }
        }

        return [
            'load_1m' => $load1,
            'load_5m' => $load5,
            'load_15m' => $load15,
            'uptime_seconds' => $uptimeSec,
        ];
    }
}
