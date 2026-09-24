<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    protected PaymentWebhookService $webhookService;

    public function __construct(PaymentWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle bKash IPN / Webhook notification.
     */
    public function handleBkashWebhook(Request $request): JsonResponse
    {
        Log::info('bKash Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->all()]);

        // Verify cryptographic HMAC-SHA256 signature & replay attack timestamp
        if (!$this->webhookService->verifySignature($request, 'bkash')) {
            Log::warning('bKash Webhook Signature Verification Failed');
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired signature'], 401);
        }

        $result = $this->webhookService->processWebhook('bkash', $request->all());

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'] ?? 'Processed',
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Handle Nagad Webhook notification.
     */
    public function handleNagadWebhook(Request $request): JsonResponse
    {
        Log::info('Nagad Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->all()]);

        if (!$this->webhookService->verifySignature($request, 'nagad')) {
            Log::warning('Nagad Webhook Signature Verification Failed');
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired signature'], 401);
        }

        $result = $this->webhookService->processWebhook('nagad', $request->all());

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'] ?? 'Processed',
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Handle Generic / Aggregator Webhooks (e.g. SSLCommerz / Shurjopay).
     */
    public function handleGenericWebhook(Request $request, string $gateway): JsonResponse
    {
        Log::info("Generic Webhook Received ({$gateway})", ['headers' => $request->headers->all(), 'body' => $request->all()]);

        if (!$this->webhookService->verifySignature($request, $gateway)) {
            Log::warning("Generic Webhook Signature Verification Failed for {$gateway}");
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired signature'], 401);
        }

        $result = $this->webhookService->processWebhook($gateway, $request->all());

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'] ?? 'Processed',
        ], $result['success'] ? 200 : 400);
    }
}
