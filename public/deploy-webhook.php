<?php

// Keep the existing webhook URL and use Laravel's cached configuration.
use App\Http\Controllers\Webhook\GitHubDeployWebhookController;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$request = Request::capture();
$app->instance('request', $request);
$app->make(Kernel::class)->bootstrap();
$response = $app->make(GitHubDeployWebhookController::class)->handle($request);
$response->send();
