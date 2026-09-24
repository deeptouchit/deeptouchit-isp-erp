<?php

namespace App\Services\Email;

use App\Models\ActivityLog;
use App\Models\EmailDomain;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailSecurityService
{
    use CommandExecutor;

    protected string $opendkimDir = '/etc/opendkim';

    /**
     * Generate 2048-bit RSA DKIM keypair and register with OpenDKIM.
     */
    public function generateDkimKeypair(EmailDomain $domain, string $selector = 'default', ?int $adminId = null): array
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        if (!$res) {
            return ['success' => false, 'error' => 'Failed to generate OpenSSL 2048-bit RSA key.'];
        }

        openssl_pkey_export($res, $privateKey);
        $pubKeyDetails = openssl_pkey_get_details($res);
        $publicKeyPem = $pubKeyDetails['key'] ?? '';

        // Extract base64 public key for DNS TXT record
        $publicKeyClean = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\r|\n|\s/', '', $publicKeyPem);

        $domain->update([
            'dkim_status' => 'active',
            'dkim_selector' => $selector,
            'dkim_private_key' => $privateKey,
            'dkim_public_key' => $publicKeyClean,
        ]);

        $this->syncOpenDkimTables();
        $this->logAction($adminId, 'dkim_key_generated', ['domain' => $domain->domain, 'selector' => $selector]);

        return [
            'success' => true,
            'message' => "2048-bit RSA DKIM keypair generated for `{$domain->domain}`.",
            'public_key' => $publicKeyClean,
            'selector' => $selector,
        ];
    }

    /**
     * Update SPF record.
     */
    public function updateSpfRecord(EmailDomain $domain, string $spfRecord, ?int $adminId = null): array
    {
        $spfClean = trim($spfRecord);
        if (!str_starts_with($spfClean, 'v=spf1')) {
            $spfClean = 'v=spf1 ' . $spfClean;
        }

        $domain->update(['spf_record' => $spfClean]);
        $this->logAction($adminId, 'spf_record_updated', ['domain' => $domain->domain, 'spf' => $spfClean]);

        return [
            'success' => true,
            'message' => "SPF record for `{$domain->domain}` updated.",
        ];
    }

    /**
     * Update DMARC policy record.
     */
    public function updateDmarcRecord(EmailDomain $domain, string $dmarcRecord, ?int $adminId = null): array
    {
        $dmarcClean = trim($dmarcRecord);
        if (!str_starts_with($dmarcClean, 'v=DMARC1')) {
            $dmarcClean = 'v=DMARC1; ' . $dmarcClean;
        }

        $domain->update(['dmarc_record' => $dmarcClean]);
        $this->logAction($adminId, 'dmarc_record_updated', ['domain' => $domain->domain, 'dmarc' => $dmarcClean]);

        return [
            'success' => true,
            'message' => "DMARC policy for `{$domain->domain}` updated.",
        ];
    }

    /**
     * Live DNS Deliverability Verification.
     */
    public function verifyDomainDns(EmailDomain $domain): array
    {
        $domainName = $domain->domain;
        $selector = $domain->dkim_selector ?: 'default';

        $results = [
            'domain' => $domainName,
            'mx' => ['status' => 'missing', 'target' => '', 'records' => []],
            'spf' => ['status' => 'missing', 'record' => ''],
            'dkim' => ['status' => 'missing', 'selector' => $selector, 'record' => ''],
            'dmarc' => ['status' => 'missing', 'record' => ''],
            'score' => 0,
            'is_fully_verified' => false,
        ];

        $score = 0;

        // 1. Check MX
        $mxRecords = @dns_get_record($domainName, DNS_MX);
        if (!empty($mxRecords)) {
            $results['mx']['status'] = 'valid';
            $results['mx']['target'] = $mxRecords[0]['target'] ?? '';
            $results['mx']['records'] = array_map(fn($r) => ['pri' => $r['pri'], 'target' => $r['target']], $mxRecords);
            $score += 25;
        }

        // 2. Check TXT for SPF
        $txtRecords = @dns_get_record($domainName, DNS_TXT);
        if (!empty($txtRecords)) {
            foreach ($txtRecords as $txt) {
                $entry = $txt['txt'] ?? '';
                if (str_starts_with(strtolower($entry), 'v=spf1')) {
                    $results['spf']['status'] = 'valid';
                    $results['spf']['record'] = $entry;
                    $score += 25;
                    break;
                }
            }
        }

        // 3. Check DKIM TXT
        $dkimHost = "{$selector}._domainkey.{$domainName}";
        $dkimRecords = @dns_get_record($dkimHost, DNS_TXT);
        if (!empty($dkimRecords)) {
            foreach ($dkimRecords as $txt) {
                $entry = $txt['txt'] ?? '';
                if (str_contains(strtolower($entry), 'v=dkim1') || str_contains($entry, 'p=')) {
                    $results['dkim']['status'] = 'valid';
                    $results['dkim']['record'] = $entry;
                    $score += 25;
                    break;
                }
            }
        }

        // 4. Check DMARC TXT
        $dmarcHost = "_dmarc.{$domainName}";
        $dmarcRecords = @dns_get_record($dmarcHost, DNS_TXT);
        if (!empty($dmarcRecords)) {
            foreach ($dmarcRecords as $txt) {
                $entry = $txt['txt'] ?? '';
                if (str_starts_with(strtolower($entry), 'v=dmarc1')) {
                    $results['dmarc']['status'] = 'valid';
                    $results['dmarc']['record'] = $entry;
                    $score += 25;
                    break;
                }
            }
        }

        $results['score'] = $score;
        $results['is_fully_verified'] = ($score === 100);

        return $results;
    }

    /**
     * Sync OpenDKIM configuration files.
     */
    public function syncOpenDkimTables(): void
    {
        try {
            $domains = EmailDomain::where('status', 'active')->whereNotNull('dkim_private_key')->get();

            $keyTable = '';
            $signingTable = '';
            $trustedHosts = "127.0.0.1\nlocalhost\n::1\n";

            foreach ($domains as $d) {
                $selector = $d->dkim_selector ?: 'default';
                $keyDir = "{$this->opendkimDir}/keys/{$d->domain}";
                $keyFile = "{$keyDir}/{$selector}.private";

                $keyTable .= "{$selector}._domainkey.{$d->domain} {$d->domain}:{$selector}:{$keyFile}\n";
                $signingTable .= "*@{$d->domain} {$selector}._domainkey.{$d->domain}\n";
                $trustedHosts .= "{$d->domain}\n*.{$d->domain}\n";

                // Save private key on disk safely
                @mkdir("/tmp/dkim_{$d->domain}", 0750, true);
                @file_put_contents("/tmp/dkim_{$d->domain}/{$selector}.private", $d->dkim_private_key);

                $this->executeSudoCommand(['mkdir', '-p', $keyDir]);
                $this->executeSudoCommand(['cp', "/tmp/dkim_{$d->domain}/{$selector}.private", $keyFile]);
                $this->executeSudoCommand(['chown', '-R', 'opendkim:opendkim', $keyDir]);
                $this->executeSudoCommand(['chmod', '600', $keyFile]);

                @unlink("/tmp/dkim_{$d->domain}/{$selector}.private");
                @rmdir("/tmp/dkim_{$d->domain}");
            }

            @file_put_contents('/tmp/KeyTable', $keyTable);
            @file_put_contents('/tmp/SigningTable', $signingTable);
            @file_put_contents('/tmp/TrustedHosts', $trustedHosts);

            $this->executeSudoCommand(['cp', '/tmp/KeyTable', "{$this->opendkimDir}/KeyTable"]);
            $this->executeSudoCommand(['cp', '/tmp/SigningTable', "{$this->opendkimDir}/SigningTable"]);
            $this->executeSudoCommand(['cp', '/tmp/TrustedHosts', "{$this->opendkimDir}/TrustedHosts"]);

            $this->executeSudoCommand(['systemctl', 'reload-or-restart', 'opendkim']);

            @unlink('/tmp/KeyTable');
            @unlink('/tmp/SigningTable');
            @unlink('/tmp/TrustedHosts');
        } catch (\Throwable $e) {
            Log::warning("Failed to sync OpenDKIM tables: " . $e->getMessage());
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Email Security Suite: {$action}",
                'ip_address' => request()->ip() ?: '127.0.0.1',
                'user_agent' => request()->userAgent() ?: 'CLI',
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        } catch (\Throwable $e) {
            Log::error("ActivityLog error: " . $e->getMessage());
        }
    }
}
