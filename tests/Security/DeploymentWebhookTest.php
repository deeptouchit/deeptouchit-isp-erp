<?php

namespace Tests\Security;

use App\Http\Controllers\Webhook\GitHubDeployWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class DeploymentWebhookTest extends TestCase
{
    private function request(array $data, string $event = 'ping', string $secret = 'audit-secret'): Request
    {
        $body = json_encode($data);
        return Request::create('/deploy-webhook.php', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GITHUB_EVENT' => $event,
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, $secret),
        ], $body);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['deployment.secret' => 'audit-secret', 'deployment.repository' => 'audit/repository', 'deployment.branch' => 'refs/heads/main']);
    }

    public function test_missing_secret_fails_closed(): void
    {
        config(['deployment.secret' => null]);
        $response = (new GitHubDeployWebhookController)->handle($this->request([]));
        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $response = (new GitHubDeployWebhookController)->handle($this->request([], 'push', 'wrong-secret'));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_wrong_repository_is_rejected(): void
    {
        $response = (new GitHubDeployWebhookController)->handle($this->request(['repository' => ['full_name' => 'other/repository']]));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_valid_ping_works_without_deploying(): void
    {
        $response = (new GitHubDeployWebhookController)->handle($this->request(['repository' => ['full_name' => 'audit/repository']]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Webhook verified', $response->getData()->message);
    }

    public function test_master_is_not_accepted_when_main_is_configured(): void
    {
        $response = (new GitHubDeployWebhookController)->handle($this->request(['repository' => ['full_name' => 'audit/repository'], 'ref' => 'refs/heads/master'], 'push'));
        $this->assertSame('Event ignored', $response->getData()->message);
    }

    public function test_get_requests_are_rejected(): void
    {
        $response = (new GitHubDeployWebhookController)->handle(Request::create('/deploy-webhook.php'));
        $this->assertSame(405, $response->getStatusCode());
    }

    public function test_valid_push_schedules_configured_runner_without_executing_it(): void
    {
        Process::fake();
        config(['deployment.runner' => '/usr/local/sbin/isp-management-deploy']);
        $request = $this->request(['repository' => ['full_name' => 'audit/repository'], 'ref' => 'refs/heads/main'], 'push');
        $response = (new GitHubDeployWebhookController)->handle($request);
        $this->assertSame(202, $response->getStatusCode());
        Process::assertRan(fn ($process) => str_starts_with($process->command, 'sudo -n -- ')
            && str_contains($process->command, '/usr/local/sbin/isp-management-deploy'));
    }
}
