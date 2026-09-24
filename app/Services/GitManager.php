<?php

namespace App\Services;

use App\Traits\CommandExecutor;
use Illuminate\Support\Facades\Log;

class GitManager
{
    use CommandExecutor;

    /**
     * Clone or pull a repository and run deployment script.
     */
    public function deploy(string $directory, string $repoUrl, string $branch = 'main', ?string $postDeployScript = null): array
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if git initialized
        if (is_dir($directory . '/.git')) {
            $cmd = ['git', '-C', $directory, 'pull', 'origin', $branch];
        } else {
            $cmd = ['git', 'clone', '-b', $branch, $repoUrl, $directory];
        }

        $result = $this->executeCommand($cmd);

        // Execute post deploy script if specified
        $postOutput = '';
        if ($result['success'] && !empty($postDeployScript)) {
            $scriptProcess = new \Symfony\Component\Process\Process(['bash', '-c', $postDeployScript], $directory);
            $scriptProcess->run();
            $postOutput = $scriptProcess->getOutput();
        }

        return [
            'success' => $result['success'],
            'output' => $result['output'] . "\n" . $postOutput,
            'error' => $result['error'] ?? null
        ];
    }
}
