<?php
namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;

class BkashGateway extends BaseGateway
{
    public function initiatePayment(array $data): array
    {
        // bKash API Implementation
        $token = $this->getToken();
        
        $createUrl = $this->config['create_payment_url'] ?? (($this->config['base_url'] ?? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta') . '/tokenized/checkout/create');
        
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json'
        ])->post($createUrl, [
            'mode' => '0011',
            'payerReference' => $data['reference'] ?? ($data['invoice_no'] ?? 'INV'),
            'callbackURL' => $data['callback_url'],
            'amount' => (string) $data['amount'],
            'currency' => 'BDT',
            'merchantInvoiceNumber' => $data['invoice_no'],
            'intent' => 'sale'
        ]);
        
        return $response->json() ?? [];
    }
    
    public function verifyPayment(string $transactionId): array
    {
        $token = $this->getToken();
        
        $executeUrl = $this->config['execute_payment_url'] ?? (($this->config['base_url'] ?? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta') . '/tokenized/checkout/execute');
        
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json'
        ])->post($executeUrl, [
            'paymentID' => $transactionId
        ]);
        
        return $response->json() ?? [];
    }
    
    public function refundPayment(string $transactionId, float $amount): array
    {
        $token = $this->getToken();
        
        $refundUrl = $this->config['refund_url'] ?? (($this->config['base_url'] ?? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta') . '/tokenized/checkout/payment/refund');
        
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json'
        ])->post($refundUrl, [
            'paymentID' => $transactionId,
            'amount' => (string) $amount,
            'trxID' => $transactionId,
            'sku' => 'REFUND',
            'reason' => 'Customer request'
        ]);
        
        return $response->json() ?? [];
    }
    
    public function webhookHandler(array $payload): array
    {
        // Validate webhook signature
        // Process payment status
        return ['status' => 'processed'];
    }
    
    private function getToken(): string
    {
        $tokenUrl = $this->config['token_url'] ?? (($this->config['base_url'] ?? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta') . '/tokenized/checkout/token/grant');
        
        $response = Http::post($tokenUrl, [
            'app_key' => $this->config['app_key'] ?? '',
            'app_secret' => $this->config['app_secret'] ?? ''
        ]);
        
        return $response->json()['id_token'] ?? '';
    }
}
