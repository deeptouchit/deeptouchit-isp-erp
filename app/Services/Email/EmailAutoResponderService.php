<?php

namespace App\Services\Email;

use App\Models\ActivityLog;
use App\Models\EmailAccount;
use App\Models\EmailAutoResponder;
use App\Models\EmailDomain;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailAutoResponderService
{
    use CommandExecutor;

    /**
     * Create a new Auto-Responder.
     */
    public function createAutoResponder(array $data, ?int $adminId = null): array
    {
        $domainId = $data['email_domain_id'];
        $domainModel = EmailDomain::findOrFail($domainId);
        $emailPrefix = strtolower(trim($data['email_prefix']));
        $fromName = trim($data['from_name'] ?? '');
        $subject = trim($data['subject']);
        $body = trim($data['body']);
        $isHtml = (bool)($data['is_html'] ?? false);
        $intervalHours = max(1, min(720, (int)($data['interval_hours'] ?? 24)));
        $startsAt = !empty($data['starts_at']) ? date('Y-m-d H:i:s', strtotime($data['starts_at'])) : null;
        $expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;

        $fullEmail = "{$emailPrefix}@{$domainModel->domain}";

        if (EmailAutoResponder::where('email_domain_id', $domainId)->where('email', $fullEmail)->exists()) {
            return ['success' => false, 'error' => "Auto-responder for `{$fullEmail}` already exists."];
        }

        // Link with EmailAccount if exists
        $account = EmailAccount::where('email', $fullEmail)->first();

        $autoResponder = EmailAutoResponder::create([
            'subscription_id' => $domainModel->subscription_id,
            'email_domain_id' => $domainModel->id,
            'email_account_id' => $account?->id,
            'email' => $fullEmail,
            'from_name' => $fromName ?: null,
            'subject' => $subject,
            'body' => $body,
            'is_html' => $isHtml,
            'interval_hours' => $intervalHours,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        $this->syncSieveScript($autoResponder);
        $this->logAction($adminId, 'email_auto_responder_created', [
            'email' => $fullEmail,
            'subject' => $subject,
        ]);

        return [
            'success' => true,
            'message' => "Auto-responder for `{$fullEmail}` created successfully.",
            'auto_responder' => $autoResponder,
        ];
    }

    /**
     * Update an existing auto-responder.
     */
    public function updateAutoResponder(EmailAutoResponder $autoResponder, array $data, ?int $adminId = null): array
    {
        $fromName = trim($data['from_name'] ?? '');
        $subject = trim($data['subject']);
        $body = trim($data['body']);
        $isHtml = (bool)($data['is_html'] ?? false);
        $intervalHours = max(1, min(720, (int)($data['interval_hours'] ?? 24)));
        $startsAt = !empty($data['starts_at']) ? date('Y-m-d H:i:s', strtotime($data['starts_at'])) : null;
        $expiresAt = !empty($data['expires_at']) ? date('Y-m-d H:i:s', strtotime($data['expires_at'])) : null;

        $autoResponder->update([
            'from_name' => $fromName ?: null,
            'subject' => $subject,
            'body' => $body,
            'is_html' => $isHtml,
            'interval_hours' => $intervalHours,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ]);

        $this->syncSieveScript($autoResponder);
        $this->logAction($adminId, 'email_auto_responder_updated', [
            'email' => $autoResponder->email,
            'subject' => $subject,
        ]);

        return [
            'success' => true,
            'message' => "Auto-responder for `{$autoResponder->email}` updated.",
        ];
    }

    /**
     * Toggle status (active / paused).
     */
    public function toggleStatus(EmailAutoResponder $autoResponder, ?int $adminId = null): array
    {
        $newStatus = $autoResponder->status === 'active' ? 'paused' : 'active';
        $autoResponder->update(['status' => $newStatus]);

        $this->syncSieveScript($autoResponder);
        $this->logAction($adminId, "email_auto_responder_{$newStatus}", ['email' => $autoResponder->email]);

        return [
            'success' => true,
            'message' => "Auto-responder for `{$autoResponder->email}` is now {$newStatus}.",
            'status' => $newStatus,
        ];
    }

    /**
     * Delete auto-responder.
     */
    public function deleteAutoResponder(EmailAutoResponder $autoResponder, ?int $adminId = null): array
    {
        $email = $autoResponder->email;
        $autoResponder->delete();

        $this->logAction($adminId, 'email_auto_responder_deleted', ['email' => $email]);

        return [
            'success' => true,
            'message' => "Auto-responder for `{$email}` deleted successfully.",
        ];
    }

    /**
     * Generate Sieve script representation for vacation reply.
     */
    public function syncSieveScript(EmailAutoResponder $autoResponder): void
    {
        try {
            $days = max(1, round($autoResponder->interval_hours / 24));
            $sieveContent = "# Generated by DeepTouchHost Auto-Responder Engine\n" .
                "require [\"vacation\"];\n\n" .
                "if true {\n" .
                "    vacation :days {$days} :subject \"{$autoResponder->subject}\" \"{$autoResponder->body}\";\n" .
                "}\n";

            $parts = explode('@', $autoResponder->email);
            $user = $parts[0] ?? 'user';
            $domain = $parts[1] ?? 'domain';

            $targetDir = "/var/mail/vhosts/{$domain}/{$user}";
            if (file_exists($targetDir)) {
                @file_put_contents("/tmp/vacation_{$user}.sieve", $sieveContent);
                $this->executeSudoCommand(['cp', "/tmp/vacation_{$user}.sieve", "{$targetDir}/.vacation.sieve"]);
                @unlink("/tmp/vacation_{$user}.sieve");
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync Sieve vacation script: " . $e->getMessage());
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Email Auto-Responder: {$action}",
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
