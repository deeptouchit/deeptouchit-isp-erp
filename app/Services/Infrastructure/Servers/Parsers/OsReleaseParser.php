<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class OsReleaseParser
{
    public function parse(string $rawOutput): array
    {
        $data = [
            'os_name' => 'Linux',
            'os_version' => 'Unknown',
            'pretty_name' => 'Linux Generic',
            'id' => 'linux',
        ];

        $lines = explode("\n", $rawOutput);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, "\"'\t\n\r ");

                match ($key) {
                    'NAME' => $data['os_name'] = $val,
                    'VERSION_ID' => $data['os_version'] = $val,
                    'PRETTY_NAME' => $data['pretty_name'] = $val,
                    'ID' => $data['id'] = strtolower($val),
                    default => null,
                };
            }
        }

        return $data;
    }
}
