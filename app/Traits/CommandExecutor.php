<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

trait CommandExecutor
{
    protected function executeCommand(array $command, ?callable $onProgress = null): array
    {
        $process = new Process($command);
        $process->setTimeout(300);
        $process->setIdleTimeout(null);
        
        try {
            $process->run(function ($type, $buffer) use ($onProgress) {
                if ($onProgress) {
                    $onProgress($type, $buffer);
                }
            });
            
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            return [
                'success' => true,
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
                'exit_code' => $process->getExitCode()
            ];
        } catch (\Exception $e) {
            Log::error('Command execution failed: ' . $e->getMessage(), [
                'command' => implode(' ', $command),
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput()
            ];
        }
    }
    
    protected function executeSudoCommand(array $command, ?callable $onProgress = null): array
    {
        return $this->executeCommand(array_merge(['sudo'], $command), $onProgress);
    }
}
