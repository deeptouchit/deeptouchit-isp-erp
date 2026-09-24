<?php

namespace App\Services;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailManager
{
    use CommandExecutor;

    protected string $mailBaseDir = '/var/vmail';

    /**
     * Create a real virtual Maildir mailbox for Postfix / Dovecot.
     */
    public function createMailbox(string $email, string $password, int $quotaMb = 1024): array
    {
        [$user, $domain] = explode('@', strtolower(trim($email)));
        $mailboxDir = "{$this->mailBaseDir}/{$domain}/{$user}";

        try {
            // Standard Maildir folder hierarchy
            $folders = [
                $mailboxDir,
                "{$mailboxDir}/cur",
                "{$mailboxDir}/new",
                "{$mailboxDir}/tmp",
                "{$mailboxDir}/.Drafts/cur",
                "{$mailboxDir}/.Drafts/new",
                "{$mailboxDir}/.Drafts/tmp",
                "{$mailboxDir}/.Sent/cur",
                "{$mailboxDir}/.Sent/new",
                "{$mailboxDir}/.Sent/tmp",
                "{$mailboxDir}/.Trash/cur",
                "{$mailboxDir}/.Trash/new",
                "{$mailboxDir}/.Trash/tmp",
                "{$mailboxDir}/.Junk/cur",
                "{$mailboxDir}/.Junk/new",
                "{$mailboxDir}/.Junk/tmp",
            ];

            foreach ($folders as $dir) {
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
            }

            // Ensure Dovecot (vmail) and Webmail (www-data) can read and write
            $this->executeSudoCommand(['chown', '-R', 'vmail:vmail', $mailboxDir]);
            $this->executeSudoCommand(['chmod', '-R', '775', $mailboxDir]);

        } catch (\Throwable $e) {
            Log::error("EmailManager createMailbox error for {$email}: " . $e->getMessage());
        }

        return [
            'success' => true,
            'email' => $email,
            'quota_mb' => $quotaMb,
            'mailbox_dir' => $mailboxDir,
            'imap_host' => 'mail.' . $domain,
            'imap_port' => 993,
            'pop3_host' => 'mail.' . $domain,
            'pop3_port' => 995,
            'smtp_host' => 'mail.' . $domain,
            'smtp_port' => 587,
            'webmail_url' => url('/webmail'),
        ];
    }

    /**
     * Delete a virtual mailbox.
     */
    public function deleteMailbox(string $email): bool
    {
        [$user, $domain] = explode('@', strtolower(trim($email)));
        $mailboxDir = "{$this->mailBaseDir}/{$domain}/{$user}";

        try {
            if (File::exists($mailboxDir)) {
                $this->executeSudoCommand(['rm', '-rf', $mailboxDir]);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error("EmailManager deleteMailbox error for {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mailbox disk usage in MB.
     */
    public function getMailboxUsage(string $email): int
    {
        [$user, $domain] = explode('@', strtolower(trim($email)));
        $mailboxDir = "{$this->mailBaseDir}/{$domain}/{$user}";

        if (!File::exists($mailboxDir)) {
            return 0;
        }

        try {
            $output = $this->executeSudoCommand(['du', '-sm', $mailboxDir]);
            if (!empty($output)) {
                return (int) explode("\t", trim($output))[0];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return 0;
    }
}
