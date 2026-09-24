<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Webhook\GitHubDeployWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes & Gateway Webhook Endpoints
|--------------------------------------------------------------------------
*/

// GitHub Auto-Deployment Webhook Endpoint
Route::post('/webhook/github-deploy', [GitHubDeployWebhookController::class, 'handle']);
Route::post('/github/webhook', [GitHubDeployWebhookController::class, 'handle']);

Route::prefix('webhooks/payment')->group(function () {
    Route::post('bkash', [PaymentWebhookController::class, 'handleBkashWebhook'])->name('api.webhook.bkash');
    Route::post('nagad', [PaymentWebhookController::class, 'handleNagadWebhook'])->name('api.webhook.nagad');
    Route::post('{gateway}', [PaymentWebhookController::class, 'handleGenericWebhook'])->name('api.webhook.generic');
});
