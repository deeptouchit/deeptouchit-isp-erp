<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubDeployWebhookController extends Controller
{
    /**
     * Handle incoming GitHub Webhook push events to trigger auto-deployment.
     */
    public function handle(Request $request)
    {
        $secret = env('GITHUB_WEBHOOK_SECRET', config('services.github.webhook_secret'));
        $signature = $request->header('X-Hub-Signature-256');

        // Verify HMAC signature if secret is configured
        if (!empty($secret)) {
            if (!$signature) {
                Log::warning('[GitHub Webhook ISP] Missing X-Hub-Signature-256 header.');
                return response()->json(['error' => 'Missing signature header'], 403);
            }

            $payload = $request->getContent();
            $knownSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

            if (!hash_equals($knownSignature, $signature)) {
                Log::warning('[GitHub Webhook ISP] Invalid signature match.');
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $event = $request->header('X-GitHub-Event', 'push');
        if ($event === 'ping') {
            Log::info('[GitHub Webhook ISP] Ping event received successfully.');
            return response()->json(['message' => 'Pong! ISP-ERP Webhook is configured correctly.'], 200);
        }

        if ($event !== 'push') {
            return response()->json(['message' => "Event '{$event}' ignored. Only 'push' is handled."], 200);
        }

        $ref = $request->input('ref', '');
        $targetBranch = env('GITHUB_DEPLOY_BRANCH', 'refs/heads/main');

        if ($ref !== $targetBranch && $ref !== 'refs/heads/master') {
            return response()->json([
                'message' => "Push on '{$ref}' ignored. Configured target branch is '{$targetBranch}'."
            ], 200);
        }

        $commit = $request->input('head_commit.id', 'unknown');
        $author = $request->input('head_commit.author.name', 'unknown');
        $message = $request->input('head_commit.message', '');

        Log::info("[GitHub Webhook ISP] Auto-deploy triggered for commit {$commit} by {$author}: {$message}");

        $appDir = base_path();
        $deployScript = base_path('deploy.sh');

        if (file_exists($deployScript)) {
            $command = "bash {$deployScript} >> {$appDir}/storage/logs/deploy.log 2>&1 &";
            exec($command, $output, $returnCode);

            return response()->json([
                'success' => true,
                'message' => 'ISP-ERP Deployment triggered successfully.',
                'commit' => $commit,
                'author' => $author,
                'triggered_at' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'deploy.sh script not found in project root.'
        ], 500);
    }
}
