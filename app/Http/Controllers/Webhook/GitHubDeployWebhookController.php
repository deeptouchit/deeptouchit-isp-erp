<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class GitHubDeployWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(['error' => 'Method not allowed'], 405, ['Allow' => 'POST']);
        }
        $secret = config('deployment.secret');
        if (!is_string($secret) || $secret === '') {
            return response()->json(['error' => 'Deployment authentication is not configured'], 503);
        }
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);
        if (!hash_equals($expected, $request->header('X-Hub-Signature-256', ''))) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
        if ($request->input('repository.full_name') !== config('deployment.repository')) {
            return response()->json(['error' => 'Repository not allowed'], 403);
        }
        $event = $request->header('X-GitHub-Event');
        if ($event === 'ping') {
            return response()->json(['message' => 'Webhook verified']);
        }
        $branch = config('deployment.branch');
        $branch = str_starts_with($branch, 'refs/heads/') ? $branch : 'refs/heads/'.$branch;
        if ($event !== 'push' || $request->input('ref') !== $branch || $request->boolean('deleted')) {
            return response()->json(['message' => 'Event ignored']);
        }
        $script = base_path('deploy.sh');
        if (!is_file($script)) {
            return response()->json(['error' => 'Deployment script unavailable'], 503);
        }
        try {
            $runner = config('deployment.runner');
            $launch = $runner
                ? 'sudo -n -- '.escapeshellarg($runner)
                : 'DEPLOY_BRANCH='.escapeshellarg(substr($branch, strlen('refs/heads/'))).' bash '.escapeshellarg($script);
            $command = $launch.' >> '.escapeshellarg(storage_path('logs/deploy.log')).' 2>&1 < /dev/null &';
            if (!Process::run($command)->successful()) {
                throw new \RuntimeException('Unable to launch deployment');
            }
            return response()->json(['success' => true, 'message' => 'Deployment scheduled'], 202);
        } catch (\Throwable $exception) {
            Log::error('Deployment launch failed', ['exception' => get_class($exception)]);
            return response()->json(['error' => 'Deployment could not be scheduled'], 503);
        }
    }
}
