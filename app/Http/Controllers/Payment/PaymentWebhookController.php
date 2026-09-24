<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentGateway\BkashGateway;
use App\Services\PaymentGateway\SSLCommerzGateway;
use App\Services\PaymentGateway\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function bkash(Request $request): JsonResponse
    {
        $gateway = new BkashGateway(config('panel.gateways.bkash', []));
        $result = $gateway->webhookHandler($request->all());
        return response()->json($result);
    }

    public function sslcommerz(Request $request): JsonResponse
    {
        $gateway = new SSLCommerzGateway(config('panel.gateways.sslcommerz', []));
        $result = $gateway->webhookHandler($request->all());
        return response()->json($result);
    }

    public function stripe(Request $request): JsonResponse
    {
        $gateway = new StripeGateway(config('panel.gateways.stripe', []));
        $result = $gateway->webhookHandler([
            'payload' => $request->getContent(),
            'signature' => $request->header('Stripe-Signature'),
        ]);
        return response()->json($result);
    }

    public function paypal(Request $request): JsonResponse
    {
        return response()->json(['status' => 'processed']);
    }
}
