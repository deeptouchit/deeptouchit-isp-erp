<?php

namespace App\Services\Email;

use App\Models\EmailRelaySetting;
use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class EmailRelayService
{
    use CommandExecutor;

    protected string $saslPasswdFile = '/etc/postfix/sasl_passwd';

    /**
     * Get or create default relay settings.
     */
    public function getSettings(): EmailRelaySetting
    {
        return EmailRelaySetting::firstOrCreate([], [
            'is_enabled' => false,
            'mode' => 'direct',
            'provider' => 'brevo',
            'host' => 'smtp-relay.brevo.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'sender_domain' => 'deeptouchit.com',
        ]);
    }

    /**
     * Update settings and sync Postfix configuration.
     */
    public function updateSettings(array $data): array
    {
        $setting = $this->getSettings();
        $setting->update($data);

        $this->syncPostfixConfig($setting);

        return [
            'success' => true,
            'message' => 'Outbound SMTP Relay settings saved and Postfix configuration synchronized.',
            'setting' => $setting,
        ];
    }

    /**
     * Synchronize Postfix main.cf and SASL password database.
     */
    public function syncPostfixConfig(EmailRelaySetting $setting): void
    {
        try {
            if ($setting->is_enabled && $setting->mode === 'relay' && !empty($setting->host)) {
                $relayTarget = "[{$setting->host}]:{$setting->port}";

                // 1. Write SASL auth file if credentials provided
                if (!empty($setting->username) && !empty($setting->password)) {
                    $saslContent = "{$relayTarget} {$setting->username}:{$setting->password}\n";
                    $tmpFile = tempnam(sys_get_temp_dir(), 'sasl_');
                    file_put_contents($tmpFile, $saslContent);

                    $this->executeSudoCommand(['cp', $tmpFile, $this->saslPasswdFile]);
                    $this->executeSudoCommand(['chmod', '600', $this->saslPasswdFile]);
                    $this->executeSudoCommand(['postmap', $this->saslPasswdFile]);
                    @unlink($tmpFile);

                    $this->executeSudoCommand(['postconf', '-e', 'smtp_sasl_auth_enable = yes']);
                    $this->executeSudoCommand(['postconf', '-e', "smtp_sasl_password_maps = hash:{$this->saslPasswdFile}"]);
                    $this->executeSudoCommand(['postconf', '-e', 'smtp_sasl_security_options = noanonymous']);
                } else {
                    $this->executeSudoCommand(['postconf', '-e', 'smtp_sasl_auth_enable = no']);
                }

                // 2. Configure Postfix relay parameters
                $this->executeSudoCommand(['postconf', '-e', "relayhost = {$relayTarget}"]);
                $this->executeSudoCommand(['postconf', '-e', 'smtp_tls_security_level = encrypt']);
                $this->executeSudoCommand(['postconf', '-e', 'smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt']);
            } else {
                // Reset to direct delivery
                $this->executeSudoCommand(['postconf', '-e', 'relayhost = ']);
                $this->executeSudoCommand(['postconf', '-e', 'smtp_sasl_auth_enable = no']);
            }

            $this->executeSudoCommand(['systemctl', 'reload', 'postfix']);
        } catch (\Throwable $e) {
            Log::error("Failed syncing Postfix relay configuration: " . $e->getMessage());
        }
    }

    /**
     * Perform live diagnostic test delivery.
     */
    public function testRelay(string $toEmail, ?EmailRelaySetting $setting = null): array
    {
        $setting = $setting ?: $this->getSettings();
        $logs = [];
        $startTime = microtime(true);
        $success = false;

        $logs[] = "[" . date('H:i:s') . "] Starting outbound email delivery probe...";
        $logs[] = "[" . date('H:i:s') . "] Target recipient: <{$toEmail}>";
        $logs[] = "[" . date('H:i:s') . "] Delivery Mode: " . ($setting->is_enabled ? "Outbound DeepTouchHost Relay ({$setting->provider})" : "Direct Server MTA (Port 25)");

        if ($setting->is_enabled && $setting->mode === 'relay') {
            $logs[] = "[" . date('H:i:s') . "] Relay Host: {$setting->host}:{$setting->port}";
            $logs[] = "[" . date('H:i:s') . "] Encryption: {$setting->encryption}";
            $logs[] = "[" . date('H:i:s') . "] SASL Authenticated User: " . substr($setting->username, 0, 4) . '***';

            // Test socket connectivity
            $socket = @fsockopen($setting->host, $setting->port, $errno, $errstr, 6);
            if (!$socket) {
                $logs[] = "[" . date('H:i:s') . "] ❌ Connection failed to relay host: {$errstr} ({$errno})";
                $success = false;
            } else {
                $response = fgets($socket, 515);
                $logs[] = "[" . date('H:i:s') . "] 🟢 Connected to Relay Banner: " . trim($response);
                fclose($socket);
                $success = true;
            }
        }

        // Send test message through local Postfix MTA
        $fromEmail = "noreply@" . ($setting->sender_domain ?: 'deeptouchit.com');
        $subject = "DeepTouchHost Delivery Diagnostic Test - " . date('Y-m-d H:i:s');
        $body = "<h2>DeepTouchHost Delivery Diagnostic Probe</h2><p>This is a verified test email from DeepTouchHost Outbound Mail Engine.</p><p>Mode: <strong>" . ($setting->is_enabled ? "DeepTouchHost Relay ({$setting->provider})" : "Direct Postfix MTA") . "</strong></p><p>Timestamp: " . date('r') . "</p>";

        try {
            $rawMime = "From: DeepTouchHost Delivery <{$fromEmail}>\r\n" .
                "To: {$toEmail}\r\n" .
                "Subject: {$subject}\r\n" .
                "Date: " . date('r') . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/html; charset=UTF-8\r\n\r\n" .
                $body . "\r\n";

            $sendmail = '/usr/sbin/sendmail';
            if (file_exists($sendmail)) {
                $pipe = @popen("{$sendmail} -t -oi -f " . escapeshellarg($fromEmail), 'w');
                if ($pipe) {
                    fwrite($pipe, $rawMime);
                    $status = pclose($pipe);
                    if ($status === 0) {
                        $logs[] = "[" . date('H:i:s') . "] 🟢 Injected into Postfix spool queue (Exit 0).";
                        $success = true;
                    } else {
                        $logs[] = "[" . date('H:i:s') . "] ⚠️ Postfix pipe exited with status code: {$status}";
                    }
                }
            }
        } catch (\Throwable $e) {
            $logs[] = "[" . date('H:i:s') . "] ❌ Dispatch error: " . $e->getMessage();
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $logs[] = "[" . date('H:i:s') . "] Finished in {$elapsed}s. Status: " . ($success ? 'COMPLETED' : 'FAILED');

        $logText = implode("\n", $logs);

        $setting->update([
            'last_test_at' => now(),
            'last_test_status' => $success ? 'success' : 'failed',
            'last_test_log' => $logText,
        ]);

        return [
            'success' => $success,
            'logs' => $logText,
            'status' => $success ? 'success' : 'failed',
        ];
    }
}
