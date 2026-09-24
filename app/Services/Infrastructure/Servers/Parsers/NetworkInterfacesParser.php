<?php

namespace App\Services\Infrastructure\Servers\Parsers;

class NetworkInterfacesParser
{
    public function parse(string $rawOutput): array
    {
        $interfaces = [];
        $lines = explode("\n", trim($rawOutput));

        // Format for `ip -o addr`:
        // 2: eth0    inet 192.168.1.100/24 brd ...
        // 2: eth0    inet6 fe80::.../64 scope link ...
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 4) {
                $ifName = $parts[1] ?? 'unknown';
                $family = $parts[2] ?? '';
                $ipWithCidr = $parts[3] ?? '';
                $ip = explode('/', $ipWithCidr)[0];

                if (!isset($interfaces[$ifName])) {
                    $interfaces[$ifName] = [
                        'interface' => $ifName,
                        'ipv4' => [],
                        'ipv6' => [],
                    ];
                }

                if ($family === 'inet' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $interfaces[$ifName]['ipv4'][] = $ip;
                } elseif ($family === 'inet6' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $interfaces[$ifName]['ipv6'][] = $ip;
                }
            }
        }

        return array_values($interfaces);
    }
}
