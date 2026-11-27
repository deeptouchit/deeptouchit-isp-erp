<?php

namespace App\Console\Commands;

use App\Models\OwnerMailTestLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class CheckSmtpHealthCommand extends Command
{
    protected $signature = 'mail:check-health';
    protected $description = 'Check the configured SMTP endpoint and record its health';

    public function handle(): int
    {
        $settings = Setting::getGroup('smtp');
        $host = trim((string) ($settings['mail_host'] ?? ''));
        $port = (int) ($settings['mail_port'] ?? 0);
        $started = microtime(true);
        $success = false;
        if ($host !== '' && $port > 0) {
            $socket = @fsockopen($host, $port, $errno, $error, 10);
            $success = is_resource($socket);
            if ($success) fclose($socket);
        }
        OwnerMailTestLog::create([
            'recipient' => 'scheduled-health-check', 'host' => $host ?: 'not-configured',
            'port' => $port ?: null, 'encryption' => $settings['mail_encryption'] ?? 'none',
            'latency_ms' => round((microtime(true) - $started) * 1000), 'success' => $success,
            'ip_address' => null, 'tested_at' => now(),
        ]);
        $this->line($success ? 'SMTP health check passed.' : 'SMTP health check failed.');
        return $success ? self::SUCCESS : self::FAILURE;
    }
}
