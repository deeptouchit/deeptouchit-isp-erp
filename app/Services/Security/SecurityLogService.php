<?php

namespace App\Services\Security;

use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Request;

class SecurityLogService
{
    /**
     * Log a general security audit event.
     */
    public function log(
        string $eventType,
        string $description,
        string $severity = 'info',
        array $details = [],
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $actorName = null,
        string $status = 'logged'
    ): SecurityAuditLog {
        $ip = Request::ip() ?: '127.0.0.1';
        $method = Request::method() ?: 'CLI';
        $url = Request::fullUrl() ?: 'command-line';
        $userAgent = Request::header('User-Agent') ?: 'System';

        if (!$actorType) {
            if (auth()->check()) {
                $user = auth()->user();
                $actorType = $user->role ?? 'user';
                $actorId = $user->id;
                $actorName = $user->name ?? $user->email;
            } else {
                $actorType = app()->runningInConsole() ? 'system' : 'guest';
                $actorName = app()->runningInConsole() ? 'System / Cron Worker' : 'Unauthenticated Visitor';
            }
        }

        return SecurityAuditLog::create([
            'event_type' => $eventType,
            'severity' => in_array($severity, ['info', 'warning', 'critical', 'alert']) ? $severity : 'info',
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'ip_address' => $ip,
            'request_method' => $method,
            'request_url' => $url,
            'user_agent' => substr($userAgent, 0, 500),
            'description' => $description,
            'details' => $details,
            'status' => $status,
        ]);
    }

    /**
     * Log authentication failure / brute force attempt.
     */
    public function logAuthFailed(string $email, ?string $reason = null): SecurityAuditLog
    {
        return $this->log(
            eventType: 'auth_failed',
            description: "Failed login attempt for account '{$email}'. Reason: " . ($reason ?: 'Invalid credentials'),
            severity: 'warning',
            details: ['attempted_email' => $email, 'reason' => $reason],
            actorType: 'guest',
            actorName: $email,
            status: 'blocked'
        );
    }

    /**
     * Log webhook signature verification failure or tampering attempt.
     */
    public function logWebhookTampering(string $gateway, string $signature, array $payload): SecurityAuditLog
    {
        return $this->log(
            eventType: 'webhook_tampered',
            description: "Unauthorized or tampered webhook received for {$gateway} gateway. Signature verification failed.",
            severity: 'critical',
            details: ['gateway' => $gateway, 'provided_signature' => $signature, 'payload_summary' => array_keys($payload)],
            actorType: 'external_gateway',
            actorName: $gateway . ' Gateway Webhook IPN',
            status: 'blocked'
        );
    }

    /**
     * Log suspicious rate limit trigger or forbidden access attempt.
     */
    public function logSuspiciousAccess(string $path, string $reason): SecurityAuditLog
    {
        return $this->log(
            eventType: 'suspicious_access',
            description: "Suspicious request blocked on '{$path}': {$reason}",
            severity: 'alert',
            details: ['path' => $path, 'reason' => $reason],
            status: 'blocked'
        );
    }

    /**
     * Log database backup creation or integrity check event.
     */
    public function logBackupEvent(string $filename, string $action = 'created', array $metadata = []): SecurityAuditLog
    {
        return $this->log(
            eventType: 'backup_' . $action,
            description: "Database backup '{$filename}' {$action} successfully.",
            severity: 'info',
            details: array_merge(['filename' => $filename], $metadata),
            actorType: auth()->check() ? 'owner' : 'system',
            status: 'logged'
        );
    }

    /**
     * Log disaster recovery restore test event.
     */
    public function logRestoreTestEvent(string $filename, bool $passed, array $report = []): SecurityAuditLog
    {
        return $this->log(
            eventType: 'restore_test_' . ($passed ? 'passed' : 'failed'),
            description: "Sandbox restore integrity test for '{$filename}' " . ($passed ? 'PASSED with 100% integrity.' : 'FAILED.'),
            severity: $passed ? 'info' : 'critical',
            details: $report,
            actorType: auth()->check() ? 'owner' : 'system',
            status: $passed ? 'resolved' : 'alerted'
        );
    }
}
