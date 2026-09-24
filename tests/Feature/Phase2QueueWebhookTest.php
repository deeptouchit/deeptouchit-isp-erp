<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TenantWallet;
use App\Models\User;
use App\Services\Payment\PaymentWebhookService;
use App\Services\Queue\QueueMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase2QueueWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected SaasPlan $plan;
    protected TenantSubscription $subscription;
    protected SaasInvoice $invoice;
    protected User $ownerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerUser = User::factory()->create([
            'email' => 'owner@somitysoft.com',
            'role' => 'owner',
        ]);

        $this->plan = SaasPlan::create([
            'code' => 'PRO-ISP',
            'name' => 'Professional ISP Plan',
            'slug' => 'professional-isp-plan',
            'monthly_price' => 2500,
            'price_monthly' => 2500,
            'price_quarterly' => 7000,
            'price_half_yearly' => 13500,
            'price_yearly' => 25000,
            'customer_limit' => 1000,
            'max_clients' => 1000,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Delta Broadband Network',
            'company_name' => 'Delta Broadband Network Ltd',
            'slug' => 'deltanet',
            'domain' => 'deltanet.somitysoft.com',
            'database_name' => 'tenant_deltanet',
            'email' => 'contact@deltanet.com',
            'phone' => '01811223344',
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $this->subscription = TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'next_billing_date' => now()->addMonth(),
            'auto_renew' => true,
        ]);

        $this->invoice = SaasInvoice::create([
            'tenant_id' => $this->tenant->id,
            'tenant_subscription_id' => $this->subscription->id,
            'invoice_no' => 'INV-DELTA-001',
            'billing_cycle' => 'monthly',
            'amount' => 2500,
            'subtotal' => 2500,
            'total_amount' => 2500,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'due_amount' => 2500,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_webhook_hmac_signature_verification_succeeds_for_valid_payload(): void
    {
        $secret = 'test_bkash_secret_key_12345';
        Setting::set('bkash_webhook_secret', $secret);

        $payload = json_encode([
            'paymentID' => 'BKX-TEST-VALID-01',
            'status' => 'Successful',
            'trxID' => 'TRXBK001122',
            'amount' => 2500,
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        $request = Request::create('/api/webhooks/payment/bkash', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
            'HTTP_X_TIMESTAMP' => (string)$timestamp,
        ], $payload);

        $service = app(PaymentWebhookService::class);
        $isValid = $service->verifySignature($request, 'bkash');

        $this->assertTrue($isValid);
    }

    public function test_webhook_hmac_signature_verification_fails_for_tampered_payload_or_wrong_secret(): void
    {
        $secret = 'test_bkash_secret_key_12345';
        Setting::set('bkash_webhook_secret', $secret);

        $payload = json_encode(['paymentID' => 'BKX-TEST-01', 'amount' => 2500]);
        $timestamp = time();
        $invalidSignature = 'tampered_or_invalid_signature_hash';

        $request = Request::create('/api/webhooks/payment/bkash', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $invalidSignature,
            'HTTP_X_TIMESTAMP' => (string)$timestamp,
        ], $payload);

        $service = app(PaymentWebhookService::class);
        $isValid = $service->verifySignature($request, 'bkash');

        $this->assertFalse($isValid);
    }

    public function test_webhook_rejects_expired_replay_attacks(): void
    {
        $secret = 'test_bkash_secret_key_12345';
        Setting::set('bkash_webhook_secret', $secret);

        $payload = json_encode(['paymentID' => 'BKX-TEST-01', 'amount' => 2500]);
        // Timestamp from 10 minutes ago (600s > 300s limit)
        $expiredTimestamp = time() - 600;
        $signature = hash_hmac('sha256', "{$expiredTimestamp}.{$payload}", $secret);

        $request = Request::create('/api/webhooks/payment/bkash', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
            'HTTP_X_TIMESTAMP' => (string)$expiredTimestamp,
        ], $payload);

        $service = app(PaymentWebhookService::class);
        $isValid = $service->verifySignature($request, 'bkash');

        $this->assertFalse($isValid);
    }

    public function test_webhook_processing_settles_invoice_and_enforces_idempotency(): void
    {
        $trxRef = 'BKX-WEBHOOK-SETTLE-01';

        $transaction = PaymentTransaction::create([
            'tenant_id' => $this->tenant->id,
            'saas_invoice_id' => $this->invoice->id,
            'payment_method' => 'bkash',
            'gateway' => 'bkash',
            'transaction_reference' => $trxRef,
            'amount' => 2500,
            'currency' => 'BDT',
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        $service = app(PaymentWebhookService::class);

        // 1. Process first webhook callback
        $res1 = $service->processWebhook('bkash', [
            'transaction_reference' => $trxRef,
            'status' => 'success',
            'trxID' => 'BK_TRX_99998888',
            'amount' => 2500,
        ], $this->tenant->id);

        $this->assertTrue($res1['success']);
        $this->invoice->refresh();
        $this->assertEquals('paid', $this->invoice->status);
        $this->assertEquals(0, (float)$this->invoice->due_amount);

        // 2. Process duplicate webhook callback (Idempotency test)
        $res2 = $service->processWebhook('bkash', [
            'transaction_reference' => $trxRef,
            'status' => 'success',
            'trxID' => 'BK_TRX_99998888',
            'amount' => 2500,
        ], $this->tenant->id);

        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['already_processed'] ?? false);
    }

    public function test_queue_monitor_service_reports_queue_health_and_failed_jobs(): void
    {
        $queueService = app(QueueMonitorService::class);

        // Update cron heartbeat
        Artisan::call('system:heartbeat');

        $health = $queueService->getQueueHealth();
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('pending_jobs', $health);
        $this->assertArrayHasKey('failed_jobs', $health);
        $this->assertEquals('healthy', $health['heartbeat_status']);

        // Insert dummy failed job to test retry/delete
        $failedJobId = DB::table('failed_jobs')->insertGetId([
            'uuid' => (string)\Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\Jobs\SendBillingReminderJob']),
            'exception' => 'RuntimeError: Test connection timeout',
            'failed_at' => now(),
        ]);

        $failedList = $queueService->getFailedJobs();
        $this->assertNotEmpty($failedList);
        $this->assertEquals($failedJobId, $failedList[0]['id']);

        // Test delete single failed job
        $deleted = $queueService->forgetJob($failedJobId);
        $this->assertTrue($deleted);
    }

    public function test_owner_can_access_automation_hub_and_view_queue_and_failed_jobs(): void
    {
        $response = $this->actingAs($this->ownerUser)
            ->get(route('owner.automation.index'));

        $response->assertStatus(200);
        $response->assertSee('SaaS Automation');
        $response->assertSee('Server Host Cron Scheduler');
        $response->assertSee('bKash Webhook / IPN URL');
    }
}
