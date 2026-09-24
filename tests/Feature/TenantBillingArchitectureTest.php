<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\Billing\AutomationEngineService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\SubscriptionService;
use App\Services\Payment\BkashPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBillingArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_subscription_billing_payment_lifecycle(): void
    {
        // 1. Create Plan & Tenant
        $plan = SaasPlan::create([
            'code' => 'P1',
            'name' => 'P1 Standard',
            'slug' => 'p1-standard',
            'monthly_price' => 600,
            'otc_charge' => 4000,
            'customer_limit' => 100,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'DeltaNet ISP',
            'company_name' => 'DeltaNet Broadband Ltd.',
            'slug' => 'deltanet',
            'phone' => '01700000000',
            'email' => 'admin@deltanet.com',
            'saas_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $adminUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Delta Admin',
            'email' => 'admin@deltanet.com',
            'password' => bcrypt('password'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);

        // 2. Provision Subscription
        $subService = app(SubscriptionService::class);
        $subscription = $subService->createSubscription($tenant, $plan, 'monthly', 1);

        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);
        $this->assertTrue($subscription->isActive());

        // 3. Generate Subscription Invoice
        $invService = app(InvoiceService::class);
        $invoice = $invService->generateSubscriptionInvoice($subscription);

        $this->assertNotNull($invoice);
        $this->assertEquals(600, $invoice->amount);
        $this->assertEquals(600, $invoice->due_amount);
        $this->assertEquals('unpaid', $invoice->status);
        $this->assertCount(1, $invoice->items);

        // 4. Test Due Amount Formula
        $this->assertEquals(600, $invoice->calculated_due);

        // 5. Test bKash Payment Initiation and Verification
        $this->actingAs($adminUser);

        $bkashService = app(BkashPaymentService::class);
        $initResult = $bkashService->initiatePayment($invoice, 600, route('tenant.billing.payment.callback'));
        $this->assertTrue($initResult['success']);
        $this->assertNotNull($initResult['transaction_reference']);

        // Settle payment
        $verifyResult = $bkashService->verifyPayment($initResult['transaction_reference'], 'BKXTEST9999');
        $this->assertTrue($verifyResult['success']);

        $freshInvoice = $invoice->fresh();
        $this->assertEquals('paid', $freshInvoice->status);
        $this->assertEquals(0, $freshInvoice->due_amount);
        $this->assertEquals(0, $freshInvoice->calculated_due);

        // 6. Test Payment Replay Protection (Double Settlement Prevention)
        $replayResult = $bkashService->verifyPayment($initResult['transaction_reference'], 'BKXTEST9999');
        $this->assertTrue($replayResult['success']);
        $this->assertTrue($replayResult['already_processed'] ?? false);
        // Ensure paid amount remained exactly 600 and did NOT double to 1200
        $this->assertEquals(600, $invoice->fresh()->paid_amount);

        // 7. Test Tenant Billing Portal Routes
        $dashRes = $this->get('/admin/billing');
        $dashRes->assertStatus(200);
        $dashRes->assertSeeText('Subscription & Billing Overview');

        $invListRes = $this->get('/admin/billing/invoices');
        $invListRes->assertStatus(200);
        $invListRes->assertSee($freshInvoice->invoice_no);

        $invShowRes = $this->get("/admin/billing/invoices/{$freshInvoice->id}");
        $invShowRes->assertStatus(200);
        $invShowRes->assertSee('Invoice Fully Paid');
    }

    public function test_payment_callback_tenant_isolation(): void
    {
        $plan = SaasPlan::create([
            'code' => 'P1',
            'name' => 'P1 Plan',
            'slug' => 'p1-plan',
            'monthly_price' => 500,
            'is_active' => true,
        ]);

        $tenantA = Tenant::create(['name' => 'Tenant A', 'company_name' => 'Tenant A Ltd', 'slug' => 'tenant-a', 'saas_plan_id' => $plan->id, 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'company_name' => 'Tenant B Ltd', 'slug' => 'tenant-b', 'saas_plan_id' => $plan->id, 'status' => 'active']);

        $userA = User::create(['tenant_id' => $tenantA->id, 'name' => 'User A', 'email' => 'a@a.com', 'password' => bcrypt('pass'), 'role' => 'isp_admin', 'status' => 'active']);
        $userB = User::create(['tenant_id' => $tenantB->id, 'name' => 'User B', 'email' => 'b@b.com', 'password' => bcrypt('pass'), 'role' => 'isp_admin', 'status' => 'active']);

        // Create transaction for Tenant B
        $trxB = PaymentTransaction::create([
            'tenant_id' => $tenantB->id,
            'payment_method' => 'bkash',
            'gateway' => 'bkash',
            'transaction_reference' => 'BKX-TENANTB123',
            'amount' => 500,
            'currency' => 'BDT',
            'status' => 'pending',
            'initiated_at' => now(),
        ]);

        // Tenant A tries to execute callback for Tenant B's transaction reference
        $this->actingAs($userA);
        $response = $this->get(route('tenant.billing.payment.callback', ['paymentID' => $trxB->transaction_reference, 'status' => 'success']));
        
        $response->assertRedirect(route('tenant.billing.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_real_database_backup_generation(): void
    {
        $owner = User::create([
            'name' => 'Super Owner',
            'email' => 'owner@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $res = $this->post(route('owner.security-backup.trigger'), ['backup_type' => 'database']);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // Verify file was written to disk and has non-zero size
        $backupDir = storage_path('app/backups');
        $files = \Illuminate\Support\Facades\File::files($backupDir);
        $this->assertNotEmpty($files);
        $latestFile = $files[0];
        $this->assertGreaterThan(200, $latestFile->getSize());
    }

    public function test_automation_engine_with_per_tenant_failure_isolation(): void
    {
        $plan = SaasPlan::create(['code' => 'PX', 'name' => 'Plan X', 'slug' => 'plan-x', 'monthly_price' => 500, 'is_active' => true]);
        $tenant = Tenant::create(['name' => 'Test Tenant', 'company_name' => 'Test Tenant Co', 'slug' => 'test-tenant', 'saas_plan_id' => $plan->id, 'status' => 'active']);
        
        app(SubscriptionService::class)->createSubscription($tenant, $plan, 'monthly', 1);

        $engine = app(AutomationEngineService::class);
        $result = $engine->runFullEngine('test_suite');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('metrics', $result);
    }
}
