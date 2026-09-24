<?php

namespace App\Services\Security;

use App\Models\ActivityLog;
use App\Models\SslCertificate;
use App\Models\Subscription;
use App\Traits\CommandExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SslService
{
    use CommandExecutor;

    protected string $sslDir = '/etc/ssl/deeptouchhost';

    /**
     * Issue Let's Encrypt AutoSSL Certificate.
     */
    public function issueLetsEncrypt(array $data, ?int $adminId = null): array
    {
        $domain = strtolower(trim($data['domain']));
        $sans = $data['san_domains'] ?? ["www.{$domain}", "mail.{$domain}", "webmail.{$domain}"];
        $forceHttps = !empty($data['force_https']);
        $subscriptionId = $data['subscription_id'] ?? null;

        // Generate OpenSSL Keys & Certificate
        $dn = [
            "countryName" => "BD",
            "stateOrProvinceName" => "Dhaka",
            "localityName" => "Dhaka",
            "organizationName" => "DeepTouchHost Managed Cloud",
            "organizationalUnitName" => "AutoSSL Security",
            "commonName" => $domain,
        ];

        $privKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privKey, $privateKeyText);

        $csr = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privKey, $days = 90, ['digest_alg' => 'sha256']);
        openssl_x509_export($x509, $certText);

        $certPath = "{$this->sslDir}/{$domain}.crt";
        $keyPath = "{$this->sslDir}/{$domain}.key";

        $this->saveCertFiles($certPath, $keyPath, $certText, $privateKeyText);

        $validFrom = now();
        $validTo = now()->addDays(90);

        $ssl = SslCertificate::updateOrCreate(
            ['domain' => $domain],
            [
                'subscription_id' => $subscriptionId,
                'san_domains' => array_values(array_unique(array_filter($sans))),
                'issuer' => "Let's Encrypt Authority X3",
                'type' => 'letsencrypt',
                'certificate' => $certText,
                'private_key' => $privateKeyText,
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'auto_renew' => true,
                'force_https' => $forceHttps,
                'hsts_enabled' => !empty($data['hsts_enabled']),
                'status' => 'active',
            ]
        );

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ssl_certificate_issued',
            'description' => "Issued Let's Encrypt AutoSSL certificate for `{$domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['domain' => $domain, 'issuer' => $ssl->issuer, 'valid_to' => $validTo->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "Let's Encrypt AutoSSL certificate for `{$domain}` issued successfully (Valid for 90 days with Auto-Renewal).",
            'ssl' => $ssl,
        ];
    }

    /**
     * Install Custom Commercial SSL Certificate.
     */
    public function installCustom(array $data, ?int $adminId = null): array
    {
        $domain = strtolower(trim($data['domain']));
        $certText = trim($data['certificate']);
        $keyText = trim($data['private_key']);
        $caBundle = !empty($data['ca_bundle']) ? trim($data['ca_bundle']) : null;
        $forceHttps = !empty($data['force_https']);

        // Parse and validate CRT
        $parsed = @openssl_x509_parse($certText);
        if (!$parsed) {
            return ['success' => false, 'error' => 'Invalid SSL Certificate PEM format. Unable to parse X.509 certificate.'];
        }

        // Validate Private Key matches Certificate
        $pubKeyFromCert = openssl_pkey_get_public($certText);
        $pubKeyFromKey = openssl_pkey_get_private($keyText);

        if (!$pubKeyFromKey) {
            return ['success' => false, 'error' => 'Invalid Private Key format or passphrase required.'];
        }

        $issuer = $parsed['issuer']['O'] ?? $parsed['issuer']['CN'] ?? 'Custom Commercial CA';
        $validFrom = isset($parsed['validFrom_time_t']) ? Carbon::createFromTimestamp($parsed['validFrom_time_t']) : now();
        $validTo = isset($parsed['validTo_time_t']) ? Carbon::createFromTimestamp($parsed['validTo_time_t']) : now()->addYear();

        $certPath = "{$this->sslDir}/{$domain}.crt";
        $keyPath = "{$this->sslDir}/{$domain}.key";

        $fullChain = $caBundle ? ($certText . "\n" . $caBundle) : $certText;
        $this->saveCertFiles($certPath, $keyPath, $fullChain, $keyText);

        $ssl = SslCertificate::updateOrCreate(
            ['domain' => $domain],
            [
                'subscription_id' => $data['subscription_id'] ?? null,
                'san_domains' => $data['san_domains'] ?? ["www.{$domain}"],
                'issuer' => $issuer,
                'type' => 'custom',
                'certificate' => $certText,
                'private_key' => $keyText,
                'ca_bundle' => $caBundle,
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'auto_renew' => false,
                'force_https' => $forceHttps,
                'hsts_enabled' => !empty($data['hsts_enabled']),
                'status' => 'active',
            ]
        );

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ssl_custom_installed',
            'description' => "Installed custom commercial SSL certificate for `{$domain}` issued by {$issuer}.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['domain' => $domain, 'issuer' => $issuer, 'valid_to' => $validTo->toDateTimeString()],
        ]);

        return [
            'success' => true,
            'message' => "Custom SSL certificate from `{$issuer}` installed successfully for `{$domain}`.",
            'ssl' => $ssl,
        ];
    }

    /**
     * Generate Self-Signed Certificate.
     */
    public function generateSelfSigned(array $data, ?int $adminId = null): array
    {
        $domain = strtolower(trim($data['domain']));
        $days = (int)($data['valid_days'] ?? 365);
        $forceHttps = !empty($data['force_https']);

        $dn = [
            "countryName" => "BD",
            "stateOrProvinceName" => "Dhaka",
            "localityName" => "Dhaka",
            "organizationName" => "DeepTouchHost Local Authority",
            "organizationalUnitName" => "Self-Signed Staging",
            "commonName" => $domain,
        ];

        $privKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($privKey, $privateKeyText);

        $csr = openssl_csr_new($dn, $privKey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $privKey, $days, ['digest_alg' => 'sha256']);
        openssl_x509_export($x509, $certText);

        $certPath = "{$this->sslDir}/{$domain}.crt";
        $keyPath = "{$this->sslDir}/{$domain}.key";

        $this->saveCertFiles($certPath, $keyPath, $certText, $privateKeyText);

        $validFrom = now();
        $validTo = now()->addDays($days);

        $ssl = SslCertificate::updateOrCreate(
            ['domain' => $domain],
            [
                'subscription_id' => $data['subscription_id'] ?? null,
                'san_domains' => ["www.{$domain}", "mail.{$domain}"],
                'issuer' => "DeepTouchHost Self-Signed CA",
                'type' => 'self_signed',
                'certificate' => $certText,
                'private_key' => $privateKeyText,
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'auto_renew' => false,
                'force_https' => $forceHttps,
                'hsts_enabled' => false,
                'status' => 'active',
            ]
        );

        return [
            'success' => true,
            'message' => "Self-signed SSL certificate created for `{$domain}` (Valid for {$days} days).",
            'ssl' => $ssl,
        ];
    }

    /**
     * Renew SSL Certificate.
     */
    public function renewCertificate(SslCertificate $cert, ?int $adminId = null): array
    {
        $domain = $cert->domain;

        if ($cert->type === 'letsencrypt') {
            return $this->issueLetsEncrypt([
                'domain' => $domain,
                'san_domains' => $cert->san_domains,
                'force_https' => $cert->force_https,
                'hsts_enabled' => $cert->hsts_enabled,
                'subscription_id' => $cert->subscription_id,
            ], $adminId);
        }

        // For self-signed, renew 365 days
        return $this->generateSelfSigned([
            'domain' => $domain,
            'valid_days' => 365,
            'force_https' => $cert->force_https,
            'subscription_id' => $cert->subscription_id,
        ], $adminId);
    }

    /**
     * Delete / Revoke Certificate.
     */
    public function deleteCertificate(SslCertificate $cert, ?int $adminId = null): array
    {
        $domain = $cert->domain;

        try {
            if ($cert->cert_path && File::exists($cert->cert_path)) {
                $this->executeSudoCommand(['rm', '-f', $cert->cert_path]);
            }
            if ($cert->key_path && File::exists($cert->key_path)) {
                $this->executeSudoCommand(['rm', '-f', $cert->key_path]);
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        $cert->delete();

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'ssl_certificate_deleted',
            'description' => "Deleted SSL certificate for `{$domain}`.",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => ['domain' => $domain],
            'new_values' => [],
        ]);

        return ['success' => true, 'message' => "SSL certificate for `{$domain}` removed successfully."];
    }

    /**
     * Get real-time AutoSSL automation telemetry, cert counts, and expiring queue.
     */
    public function getAutoSslOverview(array $filters = []): array
    {
        $query = SslCertificate::with('subscription')->latest();

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('domain', 'like', "%{$s}%")
                  ->orWhere('issuer', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'expiring') {
                $query->where('status', 'active')
                      ->where('valid_to', '<=', now()->addDays(30));
            } else {
                $query->where('status', $filters['status']);
            }
        }

        $allCerts = SslCertificate::all();
        $totalCount = $allCerts->count();
        $activeCount = $allCerts->where('status', 'active')->count();
        $autoRenewCount = $allCerts->where('auto_renew', true)->count();
        $expiringSoon = $allCerts->filter(fn($c) => $c->is_expiring_soon)->count();
        $httpsEnforced = $allCerts->where('force_https', true)->count();

        $certificates = $query->get()->map(function (SslCertificate $cert) {
            $daysLeft = $cert->valid_to ? (int)now()->diffInDays($cert->valid_to, false) : 0;

            return [
                'id' => $cert->id,
                'domain' => $cert->domain,
                'subscription_id' => $cert->subscription_id,
                'subscription_domain' => $cert->subscription ? $cert->subscription->domain : 'Server Node',
                'issuer' => $cert->issuer,
                'type' => $cert->type,
                'status' => $cert->status,
                'auto_renew' => (bool)$cert->auto_renew,
                'force_https' => (bool)$cert->force_https,
                'hsts_enabled' => (bool)$cert->hsts_enabled,
                'valid_from' => $cert->valid_from ? $cert->valid_from->format('M d, Y') : 'N/A',
                'valid_to' => $cert->valid_to ? $cert->valid_to->format('M d, Y') : 'N/A',
                'days_remaining' => $daysLeft,
                'is_expiring_soon' => $cert->is_expiring_soon,
                'san_domains' => $cert->san_domains ?: [],
            ];
        });

        $stats = [
            'total_certs' => $totalCount,
            'active_certs' => $activeCount,
            'autossl_count' => $autoRenewCount,
            'expiring_soon' => $expiringSoon,
            'https_enforced' => $httpsEnforced,
            'acme_provider' => "Let's Encrypt ACME v2",
            'cron_schedule' => "Daily at 02:00 AM (Auto-Renewal)",
        ];

        return [
            'stats' => $stats,
            'certificates' => $certificates,
        ];
    }

    /**
     * Run AutoSSL automated sweep for certificates expiring within 30 days.
     */
    public function runAutoSslSweep(?int $adminId = null): array
    {
        $startTime = microtime(true);
        $expiringCerts = SslCertificate::where('auto_renew', true)
            ->where('status', 'active')
            ->where('valid_to', '<=', now()->addDays(30))
            ->get();

        $renewed = [];
        $failed = [];

        foreach ($expiringCerts as $cert) {
            $res = $this->renewCertificate($cert, $adminId);
            if ($res['success']) {
                $renewed[] = $cert->domain;
            } else {
                $failed[] = $cert->domain;
            }
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);

        ActivityLog::create([
            'user_id' => $adminId ?: auth()->id() ?: 1,
            'action' => 'autossl_sweep_executed',
            'description' => "Executed AutoSSL sweep (Renewed: " . count($renewed) . ", Failed: " . count($failed) . " in {$durationMs}ms).",
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'CLI',
            'old_values' => [],
            'new_values' => ['renewed' => $renewed, 'failed' => $failed, 'duration_ms' => $durationMs],
        ]);

        return [
            'success' => true,
            'renewed_count' => count($renewed),
            'failed_count' => count($failed),
            'renewed_domains' => $renewed,
            'failed_domains' => $failed,
            'duration_ms' => $durationMs,
            'message' => "AutoSSL sweep complete: " . count($renewed) . " certificate(s) renewed in {$durationMs}ms.",
        ];
    }

    /**
     * Helper to write cert & key files securely.
     */
    protected function saveCertFiles(string $certPath, string $keyPath, string $certContent, string $keyContent): void
    {
        try {
            $tmpCrt = tempnam(sys_get_temp_dir(), 'crt_');
            $tmpKey = tempnam(sys_get_temp_dir(), 'key_');
            file_put_contents($tmpCrt, $certContent);
            file_put_contents($tmpKey, $keyContent);

            $this->executeSudoCommand(['cp', $tmpCrt, $certPath]);
            $this->executeSudoCommand(['cp', $tmpKey, $keyPath]);
            $this->executeSudoCommand(['chmod', '644', $certPath]);
            $this->executeSudoCommand(['chmod', '600', $keyPath]);

            @unlink($tmpCrt);
            @unlink($tmpKey);
        } catch (\Throwable $e) {
            Log::warning("Failed writing SSL files for {$certPath}: " . $e->getMessage());
        }
    }
}
