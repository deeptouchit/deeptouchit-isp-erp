<?php

namespace App\Services\Email;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantBackup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class TenantMailService
{
    /**
     * Build Symfony Mailer dynamically from Tenant SMTP Settings
     */
    protected function getMailerForTenant(Tenant $tenant): Mailer
    {
        $host = $tenant->mail_host ?: config('mail.mailers.smtp.host', '127.0.0.1');
        $port = (int) ($tenant->mail_port ?: config('mail.mailers.smtp.port', 587));
        $encryption = strtolower($tenant->mail_encryption ?: 'tls');
        $username = $tenant->mail_username ?: config('mail.mailers.smtp.username');
        $password = $tenant->mail_password ?: config('mail.mailers.smtp.password');

        $isTls = ($encryption === 'ssl' || $port === 465);
        $transport = new EsmtpTransport($host, $port, $isTls);

        if (!empty($username)) {
            $transport->setUsername($username);
        }
        if (!empty($password)) {
            $transport->setPassword($password);
        }

        return new Mailer($transport);
    }

    /**
     * Get From Address for Tenant
     */
    protected function getFromAddress(Tenant $tenant): Address
    {
        $fromEmail = $tenant->mail_from_address ?: ($tenant->email ?: config('mail.from.address', 'noreply@isp.com'));
        $fromName = $tenant->mail_from_name ?: ($tenant->company_name ?: ($tenant->name ?: config('mail.from.name', 'ISP System')));

        return new Address($fromEmail, $fromName);
    }

    /**
     * Send Database Backup Archive to Email
     */
    public function sendBackupEmail(Tenant $tenant, string $recipientEmail, TenantBackup $backup, string $fullPath): array
    {
        try {
            $mailer = $this->getMailerForTenant($tenant);
            $from = $this->getFromAddress($tenant);
            $companyName = $tenant->company_name ?? $tenant->name ?? 'ISP System';
            $dateStr = date('d M Y, h:i A');

            $subject = "🔒 [Automated Backup] {$companyName} - {$backup->filename}";

            $bodyHtml = "
            <div style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>
                    <div style='background-color: #0f172a; padding: 20px; color: #ffffff; text-align: center;'>
                        <h2 style='margin: 0; font-size: 18px; font-weight: bold; text-transform: uppercase;'>{$companyName}</h2>
                        <p style='margin: 4px 0 0 0; font-size: 12px; color: #94a3b8;'>Automated System Database Backup Archive</p>
                    </div>
                    <div style='padding: 24px;'>
                        <p style='font-size: 14px; margin-top: 0;'>Hello Administrator,</p>
                        <p style='font-size: 13px; color: #475569;'>Your scheduled automated database backup snapshot has been generated and verified successfully.</p>
                        
                        <div style='background-color: #f1f5f9; border-radius: 8px; padding: 16px; margin: 16px 0;'>
                            <table style='width: 100%; font-size: 12px; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; width: 140px; font-weight: bold;'>File Name:</td>
                                    <td style='padding: 4px 0; font-family: monospace; font-weight: bold; color: #0f172a;'>{$backup->filename}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; font-weight: bold;'>Archive Size:</td>
                                    <td style='padding: 4px 0; font-family: monospace; font-weight: bold; color: #0284c7;'>{$backup->formatted_size}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; font-weight: bold;'>Tables Dumped:</td>
                                    <td style='padding: 4px 0; font-family: monospace;'>{$backup->tables_count} Tables</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; font-weight: bold;'>Live Records:</td>
                                    <td style='padding: 4px 0; font-family: monospace; font-weight: bold; color: #16a34a;'>{$backup->records_count} Records</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; font-weight: bold;'>MD5 Checksum:</td>
                                    <td style='padding: 4px 0; font-family: monospace; font-size: 11px; color: #475569;'>{$backup->checksum_md5}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0; color: #64748b; font-weight: bold;'>Generated At:</td>
                                    <td style='padding: 4px 0;'>{$dateStr}</td>
                                </tr>
                            </table>
                        </div>

                        <p style='font-size: 12px; color: #64748b; line-height: 1.5;'>The compressed SQL archive file is attached with this email for safe offsite retention. Keep this file in a secure location.</p>
                    </div>
                    <div style='background-color: #f8fafc; padding: 12px 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #94a3b8;'>
                        ISP Management System • Automated Data Protection Engine
                    </div>
                </div>
            </div>";

            $email = (new Email())
                ->from($from)
                ->to($recipientEmail)
                ->subject($subject)
                ->html($bodyHtml);

            // Attach backup file if exists and under 25MB
            if (File::exists($fullPath)) {
                $fileSize = File::size($fullPath);
                if ($fileSize <= 25 * 1024 * 1024) { // 25 MB max attachment
                    $email->attachFromPath($fullPath, $backup->filename, 'application/gzip');
                }
            }

            $mailer->send($email);

            TenantActivityLog::create([
                'tenant_id' => $tenant->id,
                'actor_type' => 'tenant',
                'actor_id' => 1,
                'actor_name' => 'Automated Backup Engine',
                'event_type' => 'BACKUP_EMAIL_DISPATCHED',
                'description' => "Backup snapshot '{$backup->filename}' successfully sent to email {$recipientEmail}.",
                'ip_address' => '127.0.0.1',
                'user_agent' => 'SMTP Dispatcher',
            ]);

            return [
                'success' => true,
                'message' => "Backup emailed successfully to {$recipientEmail}.",
            ];
        } catch (\Throwable $e) {
            Log::error("TenantMailService: Failed to email backup for Tenant #{$tenant->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Email dispatch failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Send Test Email to verify SMTP Settings
     */
    public function testConnection(Tenant $tenant, string $testRecipient): array
    {
        try {
            $mailer = $this->getMailerForTenant($tenant);
            $from = $this->getFromAddress($tenant);
            $companyName = $tenant->company_name ?? $tenant->name ?? 'ISP System';
            $dateStr = date('d M Y, h:i:s A');

            $subject = "✅ SMTP Email Gateway Test - {$companyName}";

            $bodyHtml = "
            <div style='font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;'>
                <div style='max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;'>
                    <div style='background-color: #0284c7; padding: 20px; color: #ffffff; text-align: center;'>
                        <h2 style='margin: 0; font-size: 18px; font-weight: bold;'>SMTP Connection Successful!</h2>
                        <p style='margin: 4px 0 0 0; font-size: 12px; color: #e0f2fe;'>{$companyName}</p>
                    </div>
                    <div style='padding: 24px;'>
                        <p style='font-size: 13px; color: #334155; margin-top: 0;'>Your email gateway settings have been verified and tested successfully.</p>
                        
                        <div style='background-color: #f1f5f9; border-radius: 8px; padding: 14px; margin: 16px 0; font-size: 12px;'>
                            <p style='margin: 3px 0;'><strong>Host:</strong> {$tenant->mail_host}</p>
                            <p style='margin: 3px 0;'><strong>Port:</strong> {$tenant->mail_port} ({$tenant->mail_encryption})</p>
                            <p style='margin: 3px 0;'><strong>Sender:</strong> {$from->getAddress()}</p>
                            <p style='margin: 3px 0;'><strong>Verified Time:</strong> {$dateStr}</p>
                        </div>

                        <p style='font-size: 12px; color: #64748b;'>Automated database backups and system notifications will now be delivered using this SMTP gateway.</p>
                    </div>
                </div>
            </div>";

            $email = (new Email())
                ->from($from)
                ->to($testRecipient)
                ->subject($subject)
                ->html($bodyHtml);

            $mailer->send($email);

            return [
                'success' => true,
                'message' => "Test email successfully sent to {$testRecipient} via {$tenant->mail_host}:{$tenant->mail_port}.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "SMTP Test Failed: " . $e->getMessage(),
            ];
        }
    }
}
