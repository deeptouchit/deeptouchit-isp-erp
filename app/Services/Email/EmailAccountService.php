<?php

namespace App\Services\Email;

use App\Models\ActivityLog;
use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EmailAccountService
{
    use CommandExecutor;

    protected string $postfixDir = '/etc/postfix';
    protected string $dovecotDir = '/etc/dovecot';
    protected string $mailBaseDir = '/var/mail/vhosts';

    /**
     * Create / Provision a new Email Mailbox.
     */
    public function createAccount(array $data, ?int $adminId = null): array
    {
        $domainId = $data['email_domain_id'];
        $domainModel = EmailDomain::findOrFail($domainId);
        $username = strtolower(trim($data['username']));
        $password = $data['password'];
        $quotaMb = (int)($data['quota_mb'] ?? 1024);
        $forwardTo = !empty($data['forward_to']) ? trim($data['forward_to']) : null;

        $fullEmail = "{$username}@{$domainModel->domain}";

        if (EmailAccount::where('email', $fullEmail)->exists()) {
            return ['success' => false, 'error' => "Email mailbox `{$fullEmail}` already exists."];
        }

        // Check domain mailbox count limit
        if ($domainModel->accounts()->count() >= $domainModel->max_accounts) {
            return ['success' => false, 'error' => "Domain `{$domainModel->domain}` has reached its maximum account limit ({$domainModel->max_accounts})."];
        }

        $account = EmailAccount::create([
            'subscription_id' => $domainModel->subscription_id,
            'email_domain_id' => $domainModel->id,
            'email' => $fullEmail,
            'password' => Hash::make($password),
            'quota_mb' => $quotaMb,
            'used_quota_mb' => 0,
            'forward_to' => $forwardTo,
            'auto_responder' => null,
            'status' => 'active',
        ]);

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, 'email_account_created', ['email' => $fullEmail]);

        return [
            'success' => true,
            'message' => "Mailbox `{$fullEmail}` provisioned successfully.",
            'account' => $account,
        ];
    }

    /**
     * Change mailbox password.
     */
    public function changePassword(EmailAccount $account, string $newPassword, ?int $adminId = null): array
    {
        $account->update([
            'password' => Hash::make($newPassword),
        ]);

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, 'email_account_password_changed', ['email' => $account->email]);

        return [
            'success' => true,
            'message' => "Password for mailbox `{$account->email}` updated successfully.",
        ];
    }

    /**
     * Update mailbox quota limit.
     */
    public function updateQuota(EmailAccount $account, int $quotaMb, ?int $adminId = null): array
    {
        $account->update([
            'quota_mb' => max(100, min(1048576, $quotaMb)),
        ]);

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, 'email_account_quota_updated', ['email' => $account->email, 'quota_mb' => $quotaMb]);

        return [
            'success' => true,
            'message' => "Storage quota for `{$account->email}` updated to {$quotaMb} MB.",
        ];
    }

    /**
     * Update forwarding and autoresponder.
     */
    public function updateForwarding(EmailAccount $account, array $data, ?int $adminId = null): array
    {
        $forwardTo = !empty($data['forward_to']) ? trim($data['forward_to']) : null;
        $autoResponder = $data['auto_responder'] ?? null;

        $account->update([
            'forward_to' => $forwardTo,
            'auto_responder' => $autoResponder,
        ]);

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, 'email_account_forwarding_updated', ['email' => $account->email]);

        return [
            'success' => true,
            'message' => "Forwarding settings for `{$account->email}` saved.",
        ];
    }

    /**
     * Toggle status (active / suspended).
     */
    public function toggleStatus(EmailAccount $account, ?int $adminId = null): array
    {
        $newStatus = $account->status === 'active' ? 'suspended' : 'active';
        $account->update(['status' => $newStatus]);

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, "email_account_{$newStatus}", ['email' => $account->email]);

        return [
            'success' => true,
            'message' => "Mailbox `{$account->email}` is now {$newStatus}.",
            'status' => $newStatus,
        ];
    }

    /**
     * Delete mailbox account.
     */
    public function deleteAccount(EmailAccount $account, ?int $adminId = null): array
    {
        $email = $account->email;
        $account->delete();

        $this->syncMailboxConfigurations();
        $this->logAction($adminId, 'email_account_deleted', ['email' => $email]);

        return [
            'success' => true,
            'message' => "Mailbox `{$email}` deleted successfully.",
        ];
    }

    /**
     * Sync Postfix & Dovecot configuration files.
     */
    public function syncMailboxConfigurations(): void
    {
        try {
            $activeAccounts = EmailAccount::with('emailDomain')->where('status', 'active')->get();

            // 1. Postfix vmailbox
            $vmailboxContent = '';
            $virtualContent = '';

            foreach ($activeAccounts as $acc) {
                $parts = explode('@', $acc->email);
                $user = $parts[0] ?? 'user';
                $domain = $parts[1] ?? 'domain';

                $vmailboxContent .= "{$acc->email} {$domain}/{$user}/\n";

                if (!empty($acc->forward_to)) {
                    $virtualContent .= "{$acc->email} {$acc->forward_to}\n";
                }
            }

            // Sync catchalls
            $catchallDomains = EmailDomain::where('status', 'active')
                ->where('is_catchall_enabled', true)
                ->whereNotNull('catchall_destination')
                ->get();

            foreach ($catchallDomains as $cd) {
                $virtualContent .= "@{$cd->domain} {$cd->catchall_destination}\n";
            }

            @file_put_contents('/tmp/vmailbox_tmp', $vmailboxContent);
            @file_put_contents('/tmp/virtual_tmp', $virtualContent);

            $this->executeSudoCommand(['cp', '/tmp/vmailbox_tmp', "{$this->postfixDir}/vmailbox"]);
            $this->executeSudoCommand(['postmap', "{$this->postfixDir}/vmailbox"]);

            $this->executeSudoCommand(['cp', '/tmp/virtual_tmp', "{$this->postfixDir}/virtual"]);
            $this->executeSudoCommand(['postmap', "{$this->postfixDir}/virtual"]);

            @unlink('/tmp/vmailbox_tmp');
            @unlink('/tmp/virtual_tmp');
        } catch (\Throwable $e) {
            Log::warning("Failed to sync mailbox configurations: " . $e->getMessage());
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Email Account Manager: {$action}",
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
