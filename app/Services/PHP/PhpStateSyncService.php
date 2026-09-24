<?php

namespace App\Services\PHP;

use App\Models\Website;
use App\Models\Subscription;
use Illuminate\Support\Facades\File;

class PhpStateSyncService
{
    protected string $sitesAvailable = '/etc/nginx/sites-available';
    protected PhpDiscoveryService $discoveryService;

    public function __construct(PhpDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    /**
     * Detect drift between Database records and actual Nginx FastCGI configuration on disk.
     *
     * @return array<int, array>
     */
    public function detectDrift(): array
    {
        $websites = Website::with(['subscription.user'])->get();
        $driftReports = [];

        foreach ($websites as $site) {
            $expectedVersion = $site->php_version ?: ($site->subscription?->php_version ?: '8.2');
            $vhostPath = "{$this->sitesAvailable}/{$site->domain}.conf";
            $actualVersion = null;
            $actualSocket = null;
            $hasVhost = file_exists($vhostPath);

            if ($hasVhost) {
                $content = File::get($vhostPath);
                // Extract fastcgi_pass unix:/run/php/phpX.X-fpm...
                if (preg_match('/fastcgi_pass\s+unix:(\/run\/php\/php([0-9]+\.[0-9]+)[^;]+);/', $content, $matches)) {
                    $actualSocket = $matches[1];
                    $actualVersion = $matches[2];
                }
            }

            $isDrifted = false;
            $driftReason = null;

            if (!$hasVhost) {
                $isDrifted = true;
                $driftReason = 'Missing Nginx VHost file on server';
            } elseif (!$actualVersion) {
                $isDrifted = true;
                $driftReason = 'No FastCGI PHP socket found in Nginx configuration';
            } elseif ($actualVersion !== $expectedVersion) {
                $isDrifted = true;
                $driftReason = "Version Mismatch: DB has PHP {$expectedVersion}, but Nginx uses PHP {$actualVersion}";
            }

            if ($isDrifted) {
                $driftReports[] = [
                    'website_id' => $site->id,
                    'domain' => $site->domain,
                    'subscription_id' => $site->subscription_id,
                    'user' => $site->subscription?->user?->username ?? 'System',
                    'expected_version' => $expectedVersion,
                    'actual_version' => $actualVersion ?: 'None',
                    'actual_socket' => $actualSocket ?: 'None',
                    'vhost_exists' => $hasVhost,
                    'reason' => $driftReason,
                    'detected_at' => now()->toIso8601String(),
                ];
            }
        }

        return $driftReports;
    }
}
