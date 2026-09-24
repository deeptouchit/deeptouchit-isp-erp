<?php

namespace App\Services\Payment;

use App\Models\SaasInvoice;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function getGateway(string $gateway)
    {
        switch (strtolower($gateway)) {
            case 'bkash':
                return app(BkashPaymentService::class);
            case 'nagad':
                return app(NagadPaymentService::class);
            default:
                throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }
    }

    public function initiate(SaasInvoice $invoice, string $gateway, float $amount, string $callbackUrl): array
    {
        return $this->getGateway($gateway)->initiatePayment($invoice, $amount, $callbackUrl);
    }
}
