<?php

namespace Tests\Security;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\Tenant;
use App\Services\Payment\NagadPaymentService;
use App\Services\Payment\PaymentWebhookService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentSettlementSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Use the normal migrations, now aligned with verified live metadata.
        Http::preventStrayRequests();
    }

    private function transaction(string $gateway = 'bkash'): PaymentTransaction
    {
        $tenant = Tenant::create(['name' => 'Audit', 'company_name' => 'Audit', 'slug' => 'audit', 'status' => 'active']);
        return PaymentTransaction::create(['tenant_id' => $tenant->id, 'transaction_reference' => 'audit-reference', 'gateway' => $gateway, 'amount' => 100, 'currency' => 'BDT', 'status' => 'initiated']);
    }

    public function test_webhook_cannot_settle_different_gateway_transaction(): void
    {
        $transaction = $this->transaction();
        $result = (new PaymentWebhookService)->processWebhook('nagad', ['transaction_reference' => 'audit-reference', 'status' => 'success', 'amount' => 100]);
        $this->assertFalse($result['success']);
        $this->assertSame('initiated', $transaction->fresh()->status);
    }

    public function test_webhook_requires_matching_amount_and_currency(): void
    {
        $transaction = $this->transaction();
        foreach ([[], ['amount' => 'invalid'], ['amount' => 99], ['amount' => 100, 'currency' => 'USD']] as $fields) {
            $result = (new PaymentWebhookService)->processWebhook('bkash', array_merge(['transaction_reference' => 'audit-reference', 'status' => 'success'], $fields));
            $this->assertFalse($result['success']);
            $this->assertSame('initiated', $transaction->fresh()->status);
        }
    }

    public function test_successful_transaction_is_not_processed_twice(): void
    {
        $transaction = $this->transaction();
        $payload = ['transaction_reference' => 'audit-reference', 'status' => 'success', 'amount' => 100, 'currency' => 'BDT'];
        $service = new PaymentWebhookService;
        $this->assertTrue($service->processWebhook('bkash', $payload)['success']);
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertTrue($service->processWebhook('bkash', $payload)['already_processed']);
    }

    public function test_tenant_constraint_cannot_be_bypassed(): void
    {
        $transaction = $this->transaction();
        $result = (new PaymentWebhookService)->processWebhook('bkash', ['transaction_reference' => 'audit-reference', 'status' => 'success', 'amount' => 100], $transaction->tenant_id + 1);
        $this->assertFalse($result['success']);
        $this->assertSame('initiated', $transaction->fresh()->status);
    }

    public function test_unknown_status_does_not_fail_transaction(): void
    {
        $transaction = $this->transaction();
        $result = (new PaymentWebhookService)->processWebhook('bkash', ['transaction_reference' => 'audit-reference', 'status' => 'pending', 'amount' => 100]);
        $this->assertFalse($result['success']);
        $this->assertSame('initiated', $transaction->fresh()->status);
    }

    public function test_nagad_cannot_synthesize_success_without_credentials(): void
    {
        $transaction = $this->transaction('nagad');
        $result = (new NagadPaymentService)->verifyPayment('audit-reference', 'untrusted-callback-id', $transaction->tenant_id);
        $this->assertFalse($result['success']);
        $this->assertSame('initiated', $transaction->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_nagad_cannot_synthesize_success_on_provider_error(): void
    {
        $transaction = $this->transaction('nagad');
        Setting::set('nagad_merchant_id', 'audit-merchant');
        Setting::set('nagad_mode', 'live');
        Http::fake(['api.mynagad.com/*' => Http::response([], 503)]);
        $result = (new NagadPaymentService)->verifyPayment('audit-reference', 'untrusted-callback-id', $transaction->tenant_id);
        $this->assertFalse($result['success']);
        $this->assertSame('initiated', $transaction->fresh()->status);
    }

    public function test_nagad_accepts_only_matching_provider_confirmation(): void
    {
        $transaction = $this->transaction('nagad');
        Setting::set('nagad_merchant_id', 'audit-merchant');
        Setting::set('nagad_mode', 'live');
        Http::fake(['api.mynagad.com/*' => Http::response(['status' => 'Success', 'paymentRefId' => 'audit-reference', 'merchantId' => 'audit-merchant', 'issuerPaymentRefNo' => 'provider-id', 'amount' => '100.00'])]);
        $result = (new NagadPaymentService)->verifyPayment('audit-reference', 'untrusted-callback-id', $transaction->tenant_id);
        $this->assertTrue($result['success'], json_encode($result));
        $this->assertSame('provider-id', $result['trx_id']);
        $this->assertSame('successful', $transaction->fresh()->status);
    }
}
