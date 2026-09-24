<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class CpuInfoParser
{
    public function parse(string $rawOutput): array
    {
        $cores = 0;
        $model = 'Standard vCPU';

        $lines = explode("\n", $rawOutput);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (str_starts_with($line, 'processor')) {
                $cores++;
            } elseif (str_starts_with($line, 'model name') && str_contains($line, ':')) {
                [, $name] = explode(':', $line, 2);
                $model = trim($name);
            } elseif (is_numeric($line) && $cores === 0) {
                $cores = (int) $line;
            }
        }

        return [
            'cpu_cores' => max(1, $cores),
            'cpu_model' => $model,
        ];
    }
}
