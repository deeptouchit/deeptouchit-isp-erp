<?php

namespace Tests\Unit;

use App\Services\PaymentGateway\BkashGateway;
use App\Services\PaymentGateway\SSLCommerzGateway;
use App\Services\PaymentGateway\StripeGateway;
use PHPUnit\Framework\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_gateways_instantiation(): void
    {
        $bkash = new BkashGateway(['app_key' => 'test', 'app_secret' => 'test']);
        $this->assertInstanceOf(BkashGateway::class, $bkash);

        $ssl = new SSLCommerzGateway(['store_id' => 'test', 'store_password' => 'test']);
        $this->assertInstanceOf(SSLCommerzGateway::class, $ssl);

        $stripe = new StripeGateway(['secret_key' => 'sk_test_123']);
        $this->assertInstanceOf(StripeGateway::class, $stripe);
    }
}
