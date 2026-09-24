<?php

namespace App\Services;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class CronManager
{
    use CommandExecutor;

    /**
     * Get all active cron jobs for a Linux system user.
     */
    public function getCrons(string $systemUser): array
    {
        $result = $this->executeSudoCommand(['crontab', '-u', $systemUser, '-l']);
        if (!$result['success']) {
            return [];
        }

        $lines = explode("\n", trim($result['output']));
        $crons = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '#')) {
                $crons[] = $line;
            }
        }

        return $crons;
    }

    /**
     * Add a cron job to a system user's crontab.
     */
    public function addCron(string $systemUser, string $expression, string $command): bool
    {
        $existing = $this->getCrons($systemUser);
        $newCron = "{$expression} {$command}";
        $existing[] = $newCron;

        $content = implode("\n", array_unique($existing)) . "\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'cron_');
        file_put_contents($tmpFile, $content);

        $result = $this->executeSudoCommand(['crontab', '-u', $systemUser, $tmpFile]);
        @unlink($tmpFile);

        return $result['success'] ?? true;
    }

    /**
     * Remove a cron job.
     */
    public function removeCron(string $systemUser, string $cronLine): bool
    {
        $existing = $this->getCrons($systemUser);
        $filtered = array_filter($existing, fn($line) => trim($line) !== trim($cronLine));

        $content = implode("\n", $filtered) . "\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'cron_');
        file_put_contents($tmpFile, $content);

        $result = $this->executeSudoCommand(['crontab', '-u', $systemUser, $tmpFile]);
        @unlink($tmpFile);

        return $result['success'] ?? true;
    }
}
