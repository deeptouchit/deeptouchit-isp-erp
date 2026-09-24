<?php
namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;

class StripeGateway extends BaseGateway
{
    public function initiatePayment(array $data): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        
        $params = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($data['currency'] ?? 'usd'),
                    'product_data' => [
                        'name' => $data['product_name'] ?? 'Hosting Service'
                    ],
                    'unit_amount' => (int) ($data['amount'] * 100)
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'metadata' => [
                'invoice_id' => $data['invoice_id'] ?? '',
                'user_id' => $data['user_id'] ?? ''
            ]
        ];
        
        $response = Http::withToken($secretKey)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', $this->flattenParams($params));
        
        $result = $response->json() ?? [];
        
        return [
            'session_id' => $result['id'] ?? null,
            'url' => $result['url'] ?? null
        ];
    }
    
    public function verifyPayment(string $transactionId): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        $response = Http::withToken($secretKey)->get("https://api.stripe.com/v1/checkout/sessions/{$transactionId}");
        $session = $response->json() ?? [];
        
        return [
            'status' => $session['payment_status'] ?? 'unknown',
            'amount' => ($session['amount_total'] ?? 0) / 100,
            'currency' => $session['currency'] ?? 'usd'
        ];
    }
    
    public function refundPayment(string $transactionId, float $amount): array
    {
        $secretKey = $this->config['secret_key'] ?? '';
        $response = Http::withToken($secretKey)
            ->asForm()
            ->post('https://api.stripe.com/v1/refunds', [
                'payment_intent' => $transactionId,
                'amount' => (int) ($amount * 100)
            ]);
        
        $refund = $response->json() ?? [];
        
        return ['status' => $refund['status'] ?? 'failed'];
    }
    
    public function webhookHandler(array $payload): array
    {
        return ['status' => 'processed', 'event' => $payload['type'] ?? 'unknown'];
    }
    
    private function flattenParams(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string)$key : "{$prefix}[{$key}]";
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenParams($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
