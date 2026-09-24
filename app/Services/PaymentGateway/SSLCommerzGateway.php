<?php
namespace App\Services\PaymentGateway;

use Illuminate\Support\Facades\Http;

class SSLCommerzGateway extends BaseGateway
{
    public function initiatePayment(array $data): array
    {
        $payload = [
            'store_id' => $this->config['store_id'] ?? '',
            'store_passwd' => $this->config['store_password'] ?? '',
            'total_amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'BDT',
            'tran_id' => $data['transaction_id'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
            'ipn_url' => $data['ipn_url'],
            'cus_name' => $data['customer_name'] ?? 'Customer',
            'cus_email' => $data['customer_email'] ?? 'customer@example.com',
            'cus_phone' => $data['customer_phone'] ?? '01700000000',
            'cus_add1' => $data['customer_address'] ?? 'Dhaka',
            'product_name' => $data['product_name'] ?? 'Hosting Service',
            'product_category' => $data['category'] ?? 'Hosting',
            'product_profile' => $data['profile'] ?? 'general'
        ];
        
        $initiateUrl = $this->config['initiate_url'] ?? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
        $response = Http::asForm()->post($initiateUrl, $payload);
        
        return $response->json() ?? [];
    }
    
    public function verifyPayment(string $transactionId): array
    {
        $validationUrl = $this->config['validation_url'] ?? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
        $response = Http::asForm()->post($validationUrl, [
            'val_id' => $transactionId,
            'store_id' => $this->config['store_id'] ?? '',
            'store_passwd' => $this->config['store_password'] ?? '',
            'format' => 'json'
        ]);
        
        return $response->json() ?? [];
    }
    
    public function refundPayment(string $transactionId, float $amount): array
    {
        $refundUrl = $this->config['refund_url'] ?? 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php';
        $response = Http::asForm()->post($refundUrl, [
            'refund_amount' => $amount,
            'refund_remarks' => 'Customer request',
            'bank_tran_id' => $transactionId,
            'store_id' => $this->config['store_id'] ?? '',
            'store_passwd' => $this->config['store_password'] ?? '',
            'format' => 'json'
        ]);
        
        return $response->json() ?? [];
    }
    
    public function webhookHandler(array $payload): array
    {
        if (!$this->validateIPN($payload)) {
            return ['status' => 'invalid'];
        }
        
        return [
            'status' => 'processed',
            'transaction_id' => $payload['tran_id'] ?? null
        ];
    }
    
    private function validateIPN(array $payload): bool
    {
        return !empty($payload['tran_id']) && !empty($payload['status']);
    }
}
