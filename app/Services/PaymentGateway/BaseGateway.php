<?php
namespace App\Services\PaymentGateway;

abstract class BaseGateway
{
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    abstract public function initiatePayment(array $data): array;
    abstract public function verifyPayment(string $transactionId): array;
    abstract public function refundPayment(string $transactionId, float $amount): array;
    abstract public function webhookHandler(array $payload): array;
}
