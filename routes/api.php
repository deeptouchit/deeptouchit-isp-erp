<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes & Gateway Webhook Endpoints
|--------------------------------------------------------------------------
*/

Route::prefix('webhooks/payment')->group(function () {
    Route::post('bkash', [PaymentWebhookController::class, 'handleBkashWebhook'])->name('api.webhook.bkash');
    Route::post('nagad', [PaymentWebhookController::class, 'handleNagadWebhook'])->name('api.webhook.nagad');
    Route::post('{gateway}', [PaymentWebhookController::class, 'handleGenericWebhook'])->name('api.webhook.generic');
});
