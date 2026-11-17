<?php

namespace Tests\Security;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class StandaloneWebhookTest extends TestCase
{
    public function test_public_entry_point_bootstraps_a_fresh_request_and_rejects_unsigned_input(): void
    {
        $result = Process::path(base_path())->env([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'GITHUB_WEBHOOK_SECRET' => 'standalone-test-secret',
        ])->run([
            PHP_BINARY,
            '-r',
            '$_SERVER["REQUEST_METHOD"] = "POST"; $_SERVER["HTTP_X_GITHUB_EVENT"] = "ping"; require "public/deploy-webhook.php";',
        ]);

        $this->assertTrue($result->successful(), $result->errorOutput());
        $this->assertSame(['error' => 'Invalid signature'], json_decode($result->output(), true, 512, JSON_THROW_ON_ERROR));
    }
}
