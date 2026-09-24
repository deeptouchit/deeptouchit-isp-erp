<?php

namespace App\Services\Email;

use App\Models\ActivityLog;
use App\Models\EmailAccount;
use App\Models\EmailDomain;
use App\Models\EmailForwarder;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailForwarderService
{
    use CommandExecutor;

    protected string $postfixDir = '/etc/postfix';

    /**
     * Create a new Email Forwarder.
     */
    public function createForwarder(array $data, ?int $adminId = null): array
    {
        $domainId = $data['email_domain_id'];
        $domainModel = EmailDomain::findOrFail($domainId);
        $sourcePrefix = strtolower(trim($data['source']));
        $destinationInput = trim($data['destination']);
        $keepLocalCopy = (bool)($data['keep_local_copy'] ?? false);

        $fullSource = "{$sourcePrefix}@{$domainModel->domain}";

        if (EmailForwarder::where('email_domain_id', $domainId)->where('source', $fullSource)->exists()) {
            return ['success' => false, 'error' => "Forwarder for `{$fullSource}` already exists."];
        }

        // Clean destinations
        $destArray = array_filter(array_map('trim', explode(',', $destinationInput)));
        $cleanDest = implode(', ', $destArray);

        if (empty($cleanDest)) {
            return ['success' => false, 'error' => 'At least one valid destination email address is required.'];
        }

        $forwarder = EmailForwarder::create([
            'subscription_id' => $domainModel->subscription_id,
            'email_domain_id' => $domainModel->id,
            'source' => $fullSource,
            'destination' => $cleanDest,
            'keep_local_copy' => $keepLocalCopy,
            'status' => 'active',
        ]);

        $this->syncPostfixVirtualMap();
        $this->logAction($adminId, 'email_forwarder_created', [
            'source' => $fullSource,
            'destination' => $cleanDest,
            'keep_local_copy' => $keepLocalCopy,
        ]);

        return [
            'success' => true,
            'message' => "Email forwarder `{$fullSource}` created successfully.",
            'forwarder' => $forwarder,
        ];
    }

    /**
     * Update an existing forwarder.
     */
    public function updateForwarder(EmailForwarder $forwarder, array $data, ?int $adminId = null): array
    {
        $destinationInput = trim($data['destination']);
        $keepLocalCopy = (bool)($data['keep_local_copy'] ?? false);

        $destArray = array_filter(array_map('trim', explode(',', $destinationInput)));
        $cleanDest = implode(', ', $destArray);

        if (empty($cleanDest)) {
            return ['success' => false, 'error' => 'At least one valid destination email address is required.'];
        }

        $forwarder->update([
            'destination' => $cleanDest,
            'keep_local_copy' => $keepLocalCopy,
        ]);

        $this->syncPostfixVirtualMap();
        $this->logAction($adminId, 'email_forwarder_updated', [
            'source' => $forwarder->source,
            'destination' => $cleanDest,
        ]);

        return [
            'success' => true,
            'message' => "Forwarder `{$forwarder->source}` updated successfully.",
        ];
    }

    /**
     * Toggle forwarder status.
     */
    public function toggleStatus(EmailForwarder $forwarder, ?int $adminId = null): array
    {
        $newStatus = $forwarder->status === 'active' ? 'suspended' : 'active';
        $forwarder->update(['status' => $newStatus]);

        $this->syncPostfixVirtualMap();
        $this->logAction($adminId, "email_forwarder_{$newStatus}", ['source' => $forwarder->source]);

        return [
            'success' => true,
            'message' => "Forwarder `{$forwarder->source}` is now {$newStatus}.",
            'status' => $newStatus,
        ];
    }

    /**
     * Delete forwarder.
     */
    public function deleteForwarder(EmailForwarder $forwarder, ?int $adminId = null): array
    {
        $source = $forwarder->source;
        $forwarder->delete();

        $this->syncPostfixVirtualMap();
        $this->logAction($adminId, 'email_forwarder_deleted', ['source' => $source]);

        return [
            'success' => true,
            'message' => "Forwarder `{$source}` deleted successfully.",
        ];
    }

    /**
     * Sync full Postfix /etc/postfix/virtual table.
     */
    public function syncPostfixVirtualMap(): void
    {
        try {
            $virtualLines = [];

            // 1. Account individual forwarders
            $accounts = EmailAccount::where('status', 'active')->whereNotNull('forward_to')->where('forward_to', '!=', '')->get();
            foreach ($accounts as $acc) {
                $virtualLines[] = "{$acc->email} {$acc->forward_to}";
            }

            // 2. Dedicated EmailForwarders
            $forwarders = EmailForwarder::where('status', 'active')->get();
            foreach ($forwarders as $fwd) {
                $targets = $fwd->destination;
                if ($fwd->keep_local_copy) {
                    $targets .= ", {$fwd->source}";
                }
                $virtualLines[] = "{$fwd->source} {$targets}";
            }

            // 3. Catch-All domains
            $catchallDomains = EmailDomain::where('status', 'active')
                ->where('is_catchall_enabled', true)
                ->whereNotNull('catchall_destination')
                ->get();
            foreach ($catchallDomains as $cd) {
                $virtualLines[] = "@{$cd->domain} {$cd->catchall_destination}";
            }

            $content = implode("\n", array_unique($virtualLines)) . "\n";
            @file_put_contents('/tmp/virtual_fwd_tmp', $content);

            $this->executeSudoCommand(['cp', '/tmp/virtual_fwd_tmp', "{$this->postfixDir}/virtual"]);
            $this->executeSudoCommand(['postmap', "{$this->postfixDir}/virtual"]);

            @unlink('/tmp/virtual_fwd_tmp');
        } catch (\Throwable $e) {
            Log::warning("Failed to sync Postfix virtual map: " . $e->getMessage());
        }
    }

    protected function logAction(?int $adminId, string $action, array $newValues): void
    {
        try {
            ActivityLog::create([
                'user_id' => $adminId ?: auth()->id() ?: 1,
                'action' => $action,
                'description' => "Email Forwarder Manager: {$action}",
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
