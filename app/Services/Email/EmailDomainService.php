<?php

namespace App\Services\Email;

use App\Models\ActivityLog;
use App\Models\EmailDomain;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailDomainService
{
    use CommandExecutor;

    protected string $postfixConfigDir = '/etc/postfix';
    protected string $opendkimDir = '/etc/opendkim/keys';

    /**
     * Provision a new Email Domain with DKIM, SPF, and DMARC.
     */
    public function createDomain(array $data, ?int $adminId = null): array
    {
        $domain = strtolower(trim($data['domain']));
        $subscriptionId = $data['subscription_id'] ?? null;
        $maxAccounts = (int)($data['max_accounts'] ?? 50);
        $maxQuotaMb = (int)($data['max_quota_mb'] ?? 10240);

        if (EmailDomain::where('domain', $domain)->exists()) {
            return ['success' => false, 'error' => "Email domain `{$domain}` already exists."];
        }

        // Generate 2048-bit RSA DKIM Keypair
        $dkim = $this->generateDkimKeyPair($domain);

        $serverIp = request()->server('SERVER_ADDR') ?: '127.0.0.1';
        $spfRecord = "v=spf1 a mx ip4:{$serverIp} ~all";
        $dmarcRecord = "v=DMARC1; p=quarantine; sp=quarantine; rua=mailto:postmaster@{$domain}; pct=100";

        $emailDomain = EmailDomain::create([
            'subscription_id' => $subscriptionId,
            'domain' => $domain,
            'status' => 'active',
            'is_catchall_enabled' => false,
            'catchall_destination' => null,
            'dkim_status' => $dkim['success'] ? 'active' : 'not_generated',
            'dkim_selector' => 'default',
            'dkim_private_key' => $dkim['private_key'] ?? null,
            'dkim_public_key' => $dkim['public_key'] ?? null,
            'spf_record' => $spfRecord,
            'dmarc_record' => $dmarcRecord,
            'max_accounts' => $maxAccounts,
            'max_quota_mb' => $maxQuotaMb,
        ]);

        $this->syncPostfixVirtualDomains();
        $this->logAction($adminId, 'email_domain_created', ['domain' => $domain]);

        return [
            'success' => true,
            'message' => "Email domain `{$domain}` provisioned with 2048-bit DKIM and SPF records.",
            'domain' => $emailDomain,
        ];
    }

    /**
     * Generate 2048-bit RSA DKIM Keypair using OpenSSL.
     */
    public function generateDkimKeyPair(string $domain, string $selector = 'default'): array
    {
        try {
            $config = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];

            $res = openssl_pkey_new($config);
            if (!$res) {
                return ['success' => false, 'error' => 'Failed to initialize OpenSSL key'];
            }

            openssl_pkey_export($res, $privateKey);

            $keyDetails = openssl_pkey_get_details($res);
            $publicKeyPem = $keyDetails['key'];

            // Extract bare base64 public key for DNS TXT record
            $pubLines = explode("\n", trim($publicKeyPem));
            $cleanLines = array_filter($pubLines, function ($line) {
                return !str_starts_with($line, '---');
            });
            $cleanPublicKey = implode('', $cleanLines);

            return [
                'success' => true,
                'private_key' => $privateKey,
                'public_key' => $cleanPublicKey,
                'dns_record' => "v=DKIM1; k=rsa; p={$cleanPublicKey}",
            ];
        } catch (\Throwable $e) {
            Log::error("DKIM generation error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Perform live DNS diagnostics for an Email Domain.
     */
    public function verifyDnsRecords(EmailDomain $emailDomain): array
    {
        $domain = $emailDomain->domain;
        $selector = $emailDomain->dkim_selector ?: 'default';

        $results = [
            'domain' => $domain,
            'mx' => ['configured' => false, 'records' => [], 'status' => 'missing'],
            'spf' => ['configured' => false, 'value' => null, 'status' => 'missing'],
            'dkim' => ['configured' => false, 'value' => null, 'status' => 'missing'],
            'dmarc' => ['configured' => false, 'value' => null, 'status' => 'missing'],
            'is_fully_verified' => false,
        ];

        // 1. Check MX records
        try {
            $mxRecords = @dns_get_record($domain, DNS_MX);
            if (!empty($mxRecords)) {
                $results['mx']['configured'] = true;
                $results['mx']['records'] = array_map(fn($r) => ['target' => $r['target'] ?? '', 'pri' => $r['pri'] ?? 10], $mxRecords);
                $results['mx']['status'] = 'valid';
            }
        } catch (\Throwable $e) {}

        // 2. Check SPF (TXT records on root domain)
        try {
            $txtRecords = @dns_get_record($domain, DNS_TXT);
            if (!empty($txtRecords)) {
                foreach ($txtRecords as $txt) {
                    $val = $txt['txt'] ?? '';
                    if (str_starts_with($val, 'v=spf1')) {
                        $results['spf']['configured'] = true;
                        $results['spf']['value'] = $val;
                        $results['spf']['status'] = 'valid';
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 3. Check DKIM (TXT on selector._domainkey.domain)
        try {
            $dkimHost = "{$selector}._domainkey.{$domain}";
            $dkimTxt = @dns_get_record($dkimHost, DNS_TXT);
            if (!empty($dkimTxt)) {
                foreach ($dkimTxt as $txt) {
                    $val = $txt['txt'] ?? '';
                    if (str_contains($val, 'v=DKIM1') || str_contains($val, 'p=')) {
                        $results['dkim']['configured'] = true;
                        $results['dkim']['value'] = $val;
                        $results['dkim']['status'] = 'valid';
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 4. Check DMARC (TXT on _dmarc.domain)
        try {
            $dmarcHost = "_dmarc.{$domain}";
            $dmarcTxt = @dns_get_record($dmarcHost, DNS_TXT);
            if (!empty($dmarcTxt)) {
                foreach ($dmarcTxt as $txt) {
                    $val = $txt['txt'] ?? '';
                    if (str_starts_with($val, 'v=DMARC1')) {
                        $results['dmarc']['configured'] = true;
                        $results['dmarc']['value'] = $val;
                        $results['dmarc']['status'] = 'valid';
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        $results['is_fully_verified'] = (
            $results['mx']['configured'] &&
            $results['spf']['configured'] &&
            $results['dkim']['configured']
        );

        return $results;
    }

    /**
     * Update Catch-All routing for domain.
     */
    public function updateCatchall(EmailDomain $emailDomain, bool $enabled, ?string $destination = null, ?int $adminId = null): array
    {
        $emailDomain->update([
            'is_catchall_enabled' => $enabled,
            'catchall_destination' => $enabled ? $destination : null,
        ]);

        $this->syncPostfixVirtualDomains();
        $this->logAction($adminId, 'email_domain_catchall_updated', [
            'domain' => $emailDomain->domain,
            'enabled' => $enabled,
            'destination' => $destination,
        ]);

        return [
            'success' => true,
            'message' => "Catch-all routing for {$emailDomain->domain} updated.",
        ];
    }

    /**
     * Toggle status (active / suspended).
     */
    public function toggleStatus(EmailDomain $emailDomain, ?int $adminId = null): array
    {
        $newStatus = $emailDomain->status === 'active' ? 'suspended' : 'active';
        $emailDomain->update(['status' => $newStatus]);

        $this->syncPostfixVirtualDomains();
        $this->logAction($adminId, "email_domain_{$newStatus}", ['domain' => $emailDomain->domain]);

        return [
            'success' => true,
            'message' => "Email domain {$emailDomain->domain} is now {$newStatus}.",
            'status' => $newStatus,
        ];
    }

    /**
     * Delete email domain and accounts.
     */
    public function deleteDomain(EmailDomain $emailDomain, ?int $adminId = null): array
    {
        $domain = $emailDomain->domain;
        $emailDomain->accounts()->delete();
        $emailDomain->delete();

        $this->syncPostfixVirtualDomains();
        $this->logAction($adminId, 'email_domain_deleted', ['domain' => $domain]);

        return [
            'success' => true,
            'message' => "Email domain `{$domain}` and its mailboxes deleted.",
        ];
    }

    /**
     * Sync Active Domains with Postfix configuration.
     */
    public function syncPostfixVirtualDomains(): void
    {
        try {
            $activeDomains = EmailDomain::where('status', 'active')->pluck('domain')->toArray();
            $content = implode("\n", array_map(fn($d) => "{$d} OK", $activeDomains)) . "\n";

            $target = "{$this->postfixConfigDir}/vmailbox_domains";
            @file_put_contents('/tmp/vmailbox_domains_tmp', $content);
            $this->executeSudoCommand(['cp', '/tmp/vmailbox_domains_tmp', $target]);
            $this->executeSudoCommand(['postmap', $target]);
            @unlink('/tmp/vmailbox_domains_tmp');
        } catch (\Throwable $e) {
            Log::warning("Could not sync Postfix vmailbox_domains: " . $e->getMessage());
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Email Domain Manager: {$action}",
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
