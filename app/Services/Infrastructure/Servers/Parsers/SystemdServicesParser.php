<?php

namespace App\Services\Infrastructure\Servers\Parsers;

use App\Enums\Infrastructure\Servers\RemoteOperation;

class SystemdServicesParser
{
    public function parse(string $rawOutput): array
    {
        $detectedServices = [];
        $lines = explode("\n", trim($rawOutput));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Line typically starts with service name e.g. "nginx.service loaded active running..."
            $parts = preg_split('/\s+/', $line);
            $serviceUnit = $parts[0] ?? '';
            $serviceBase = str_replace('.service', '', $serviceUnit);

            // Match against allowlisted hosting daemons (including multi-PHP patterns like php8.3-fpm)
            foreach (RemoteOperation::ALLOWED_SERVICES as $allowed) {
                if (strtolower($serviceBase) === strtolower($allowed) && !in_array($allowed, $detectedServices, true)) {
                    $detectedServices[] = $allowed;
                }
            }
        }

        return $detectedServices;
    }
}
