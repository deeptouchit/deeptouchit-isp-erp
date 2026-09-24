<?php

namespace App\Support;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServerHelper
{
    /**
     * Cache key for detected public IP.
     */
    public const CACHE_KEY = 'server_detected_public_ip';

    /**
     * Get the authoritative Server Public IP (Auto-detected & Cached).
     */
    public static function getPublicIp(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && Cache::has(self::CACHE_KEY)) {
            $cached = Cache::get(self::CACHE_KEY);
            if (!empty($cached) && filter_var($cached, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $cached;
            }
        }

        // 1. Check database Server record primary_ip
        try {
            $server = Server::where('status', 'online')->whereNotNull('primary_ip')->first();
            if ($server && !empty($server->primary_ip) && !self::isPrivateIp($server->primary_ip)) {
                Cache::put(self::CACHE_KEY, $server->primary_ip, now()->addHours(12));
                return $server->primary_ip;
            }
        } catch (\Throwable $e) {
            // silent catch during early migrations
        }

        // 2. Check environment variable override
        $envIp = env('SERVER_PUBLIC_IP');
        if (!empty($envIp) && !self::isPrivateIp($envIp)) {
            Cache::put(self::CACHE_KEY, $envIp, now()->addHours(12));
            return $envIp;
        }

        // 3. Auto-Detect Public IP via fast external resolvers (AWS, ipify, icanhazip)
        $detectedIp = self::queryExternalResolvers();
        if (!empty($detectedIp)) {
            Cache::put(self::CACHE_KEY, $detectedIp, now()->addHours(12));
            
            // Auto sync to primary server record if empty or private
            try {
                $srv = Server::where('status', 'online')->first();
                if ($srv && (empty($srv->primary_ip) || self::isPrivateIp($srv->primary_ip))) {
                    $srv->update(['primary_ip' => $detectedIp]);
                }
            } catch (\Throwable $e) {
                // silent
            }

            return $detectedIp;
        }

        // 4. Default fallback
        $defaultFallback = '103.59.177.138';
        Cache::put(self::CACHE_KEY, $defaultFallback, now()->addHours(1));
        return $defaultFallback;
    }

    /**
     * Query fast, reliable external IP discovery resolvers.
     */
    protected static function queryExternalResolvers(): ?string
    {
        $resolvers = [
            'https://api.ipify.org',
            'https://checkip.amazonaws.com',
            'https://icanhazip.com',
            'https://ifconfig.me/ip',
        ];

        foreach ($resolvers as $url) {
            try {
                $response = Http::timeout(2)->get($url);
                if ($response->successful()) {
                    $ip = trim($response->body());
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Get internal / private NIC IP address (e.g. 10.70.0.2 on enp4s0).
     */
    public static function getPrivateIp(): string
    {
        try {
            $output = @shell_exec("ip -brief addr 2>/dev/null");
            if ($output) {
                foreach (explode("\n", trim($output)) as $line) {
                    $parts = preg_split('/\s+/', trim($line));
                    if (count($parts) >= 3 && $parts[0] !== 'lo' && $parts[1] === 'UP') {
                        return explode('/', $parts[2])[0];
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return '10.70.0.2';
    }

    /**
     * Check whether an IP address is a private / internal RFC 1918 address or loopback.
     */
    public static function isPrivateIp(?string $ip): bool
    {
        if (empty($ip)) return true;
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
