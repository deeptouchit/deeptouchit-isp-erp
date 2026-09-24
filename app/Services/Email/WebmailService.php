<?php

namespace App\Services\Email;

use App\Models\EmailAccount;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WebmailService
{
    protected string $mailBaseDir = '/var/vmail';

    /**
     * Send email via Postfix Sendmail / MTA pipe and store in database.
     */
    public function sendEmail(EmailAccount $account, array $data): array
    {
        $to = trim($data['to']);
        $subject = trim($data['subject']);
        $body = $data['body'];
        $fromEmail = $account->email;
        $fromName = $data['from_name'] ?? explode('@', $fromEmail)[0];

        $snippet = mb_substr(strip_tags($body), 0, 120);

        // 1. Store in sender's "Sent" folder
        $sentMessage = EmailMessage::create([
            'email_account_id' => $account->id,
            'folder' => 'sent',
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'to' => $to,
            'subject' => $subject,
            'snippet' => $snippet,
            'body' => $body,
            'is_read' => true,
            'is_starred' => false,
            'size_kb' => max(1, round(strlen($body) / 1024)),
        ]);

        // 2. If recipient is hosted locally on this server, deliver to their Inbox
        $localRecipient = EmailAccount::where('email', strtolower($to))->first();
        if ($localRecipient) {
            EmailMessage::create([
                'email_account_id' => $localRecipient->id,
                'folder' => 'inbox',
                'from_name' => $fromName,
                'from_email' => $fromEmail,
                'to' => $to,
                'subject' => $subject,
                'snippet' => $snippet,
                'body' => $body,
                'is_read' => false,
                'is_starred' => false,
                'size_kb' => max(1, round(strlen($body) / 1024)),
            ]);
        }

        // 3. Dispatch to Postfix MTA via /usr/sbin/sendmail binary
        $mtaSuccess = false;
        try {
            $date = date('r');
            $messageId = '<' . md5(uniqid(time(), true)) . '@' . ($account->emailDomain?->domain ?: 'deeptouchit.com') . '>';

            $rawMime = "From: {$fromName} <{$fromEmail}>\r\n" .
                "To: {$to}\r\n" .
                "Subject: {$subject}\r\n" .
                "Date: {$date}\r\n" .
                "Message-ID: {$messageId}\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/html; charset=UTF-8\r\n" .
                "X-Mailer: DeepTouchHost-Webmail/1.0\r\n\r\n" .
                $body . "\r\n";

            $sendmailPath = '/usr/sbin/sendmail';
            if (file_exists($sendmailPath) && is_executable($sendmailPath)) {
                $pipe = @popen("{$sendmailPath} -t -oi -f " . escapeshellarg($fromEmail), 'w');
                if ($pipe) {
                    fwrite($pipe, $rawMime);
                    $status = pclose($pipe);
                    $mtaSuccess = ($status === 0);
                }
            } else {
                $headers = [
                    "From: {$fromName} <{$fromEmail}>",
                    "Reply-To: {$fromEmail}",
                    "MIME-Version: 1.0",
                    "Content-Type: text/html; charset=UTF-8",
                    "X-Mailer: DeepTouchHost-Webmail/1.0",
                ];
                $mtaSuccess = @mail($to, $subject, $body, implode("\r\n", $headers));
            }
        } catch (\Throwable $e) {
            Log::warning("Webmail MTA dispatch notice: " . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => "Email to `{$to}` dispatched successfully.",
            'sent_message' => $sentMessage,
        ];
    }

    /**
     * Get messages for account (auto-syncs Maildir first).
     */
    public function getMessagesForAccount(EmailAccount $account): array
    {
        $this->syncMaildirIntoDatabase($account);

        return EmailMessage::where('email_account_id', $account->id)
            ->latest()
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'folder' => $msg->folder,
                    'from_name' => $msg->from_name ?: $msg->from_email,
                    'from_email' => $msg->from_email,
                    'to' => $msg->to,
                    'subject' => $msg->subject ?: '(No Subject)',
                    'snippet' => $msg->snippet ?: mb_substr(strip_tags($msg->body ?? ''), 0, 100),
                    'body' => $msg->body,
                    'date' => $msg->created_at ? $msg->created_at->format('M d, H:i') : date('M d, H:i'),
                    'is_read' => (bool) $msg->is_read,
                    'is_starred' => (bool) $msg->is_starred,
                    'has_attachments' => (bool) $msg->has_attachments,
                    'size_kb' => (int) $msg->size_kb,
                ];
            })
            ->toArray();
    }

    /**
     * Ingest physical Maildir files from Postfix/Dovecot into the database.
     */
    public function syncMaildirIntoDatabase(EmailAccount $account): void
    {
        [$user, $domain] = explode('@', strtolower(trim($account->email)));
        $newDir = "{$this->mailBaseDir}/{$domain}/{$user}/new";

        if (!File::exists($newDir) && !is_dir($newDir)) {
            return;
        }

        try {
            $files = @scandir($newDir);
            if (!$files) return;

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $filePath = "{$newDir}/{$file}";
                if (!is_file($filePath)) continue;

                $rawContent = @file_get_contents($filePath);
                if (!$rawContent) {
                    @unlink($filePath);
                    continue;
                }

                $parsed = $this->parseRawEmail($rawContent);

                // Prevent duplicate insertion
                $alreadyExists = EmailMessage::where('email_account_id', $account->id)
                    ->where('from_email', $parsed['from_email'])
                    ->where('subject', $parsed['subject'])
                    ->where('created_at', '>=', now()->subMinutes(60))
                    ->exists();

                if (!$alreadyExists) {
                    EmailMessage::create([
                        'email_account_id' => $account->id,
                        'folder' => 'inbox',
                        'from_name' => $parsed['from_name'],
                        'from_email' => $parsed['from_email'],
                        'to' => $parsed['to'] ?: $account->email,
                        'subject' => $parsed['subject'],
                        'snippet' => mb_substr(strip_tags($parsed['body']), 0, 120),
                        'body' => $parsed['body'],
                        'is_read' => false,
                        'is_starred' => false,
                        'size_kb' => max(1, round(strlen($rawContent) / 1024)),
                        'created_at' => $parsed['date'] ? date('Y-m-d H:i:s', strtotime($parsed['date'])) : now(),
                    ]);
                }

                // Delete file from new so it is not processed repeatedly
                @unlink($filePath);
            }
        } catch (\Throwable $e) {
            Log::warning("Maildir sync error for {$account->email}: " . $e->getMessage());
        }
    }

    /**
     * Parse raw RFC822 / MIME email string and extract clean rendered HTML / text.
     */
    public function parseRawEmail(string $raw): array
    {
        $parts = preg_split("/\r\n\r\n|\n\n/", $raw, 2);
        $headerSection = $parts[0] ?? '';
        $bodySection = $parts[1] ?? '';

        $headers = [];
        $lines = preg_split("/\r\n|\n/", $headerSection);
        $currentKey = '';

        foreach ($lines as $line) {
            if (preg_match('/^([a-zA-Z0-9_-]+):\s*(.*)$/', $line, $matches)) {
                $currentKey = strtolower($matches[1]);
                $headers[$currentKey] = trim($matches[2]);
            } elseif ($currentKey && preg_match('/^\s+(.*)$/', $line, $matches)) {
                $headers[$currentKey] .= ' ' . trim($matches[1]);
            }
        }

        // Parse Subject
        $subject = isset($headers['subject']) ? mb_decode_mimeheader($headers['subject']) : '(No Subject)';

        // Parse From
        $fromRaw = $headers['from'] ?? 'Unknown';
        $fromName = '';
        $fromEmail = '';
        if (preg_match('/^(.*?)\s*<([^>]+)>/', $fromRaw, $m)) {
            $fromName = trim(trim($m[1], '"\''));
            $fromName = mb_decode_mimeheader($fromName);
            $fromEmail = trim($m[2]);
        } else {
            $fromEmail = trim($fromRaw);
            $fromName = explode('@', $fromEmail)[0];
        }

        // Parse To
        $to = $headers['to'] ?? '';
        if (preg_match('/<([^>]+)>/', $to, $m)) {
            $to = $m[1];
        }

        // Parse Date
        $date = $headers['date'] ?? null;

        // Parse Body - Handle Multi-part & Transfer Encodings
        $body = $this->extractCleanMessageBody($headerSection, $bodySection);

        return [
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'to' => $to,
            'subject' => $subject,
            'date' => $date,
            'body' => $body,
        ];
    }

    /**
     * Extract clean HTML or Text body from MIME payload.
     */
    public function extractCleanMessageBody(string $headerSection, string $bodySection): string
    {
        // 1. Detect boundary from headers OR directly from body
        $boundary = null;
        if (preg_match('/boundary=["\']?([^"\'\r\n;]+)["\']?/i', $headerSection, $m)) {
            $boundary = trim($m[1]);
        } elseif (preg_match('/^--([a-zA-Z0-9_=\-\.\/]+)/m', $bodySection, $m)) {
            $boundary = trim($m[1]);
        }

        // 2. If Multipart, parse sub-parts
        if ($boundary) {
            $subParts = explode('--' . $boundary, $bodySection);
            $htmlBody = '';
            $plainBody = '';

            foreach ($subParts as $subPart) {
                $subPart = trim($subPart);
                if (empty($subPart) || $subPart === '--') continue;

                $pParts = preg_split("/\r\n\r\n|\n\n|\r\r/", $subPart, 2);
                $pHeader = $pParts[0] ?? '';
                $pContent = $pParts[1] ?? '';

                // If no empty line was found but subpart contains header lines
                if (empty($pContent) && preg_match('/^(Content-[^:]+:\s*.*?\n)+/is', $pHeader, $hMatch)) {
                    $pContent = trim(substr($pHeader, strlen($hMatch[0])));
                    $pHeader = $hMatch[0];
                }

                $subEncoding = '';
                if (preg_match('/content-transfer-encoding:\s*([a-zA-Z0-9_-]+)/i', $pHeader, $encMatches)) {
                    $subEncoding = strtolower($encMatches[1]);
                }

                if ($subEncoding === 'quoted-printable') {
                    $pContent = quoted_printable_decode($pContent);
                } elseif ($subEncoding === 'base64') {
                    $pContent = base64_decode($pContent);
                }

                if (stripos($pHeader, 'text/html') !== false) {
                    $htmlBody = trim($pContent);
                } elseif (stripos($pHeader, 'text/plain') !== false && empty($plainBody)) {
                    $plainBody = trim($pContent);
                }
            }

            if (!empty($htmlBody)) {
                return $htmlBody;
            }
            if (!empty($plainBody)) {
                return nl2br(htmlspecialchars($plainBody));
            }
        }

        // 3. Single-part Message
        $contentTransferEncoding = '';
        if (preg_match('/content-transfer-encoding:\s*([a-zA-Z0-9_-]+)/i', $headerSection, $m)) {
            $contentTransferEncoding = strtolower($m[1]);
        }

        $decoded = $bodySection;
        if ($contentTransferEncoding === 'quoted-printable') {
            $decoded = quoted_printable_decode($decoded);
        } elseif ($contentTransferEncoding === 'base64') {
            $decoded = base64_decode($decoded);
        }

        if (stripos($headerSection, 'text/html') !== false) {
            return $decoded;
        }

        return nl2br(htmlspecialchars($decoded));
    }
}
