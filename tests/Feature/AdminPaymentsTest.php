<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected Subscription $subscription;
    protected Invoice $invoice;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Starter Cloud NVMe',
            'slug' => 'starter-cloud-nvme',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
        ]);

        $server = Server::create([
            'name' => 'Node 1',
            'hostname' => 'node1.deeptouchhost.local',
            'ip_address' => '103.59.177.138',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'paymentdomain.com',
            'username' => 'paydomain',
            'price' => 299.00,
            'status' => 'suspended',
            'next_billing_date' => now()->subDays(1),
            'expires_at' => now()->subDays(1),
        ]);

        $this->invoice = Invoice::create([
            'user_id' => $this->clientUser->id,
            'subscription_id' => $this->subscription->id,
            'total_amount' => 299.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'paid_amount' => 0.00,
            'due_amount' => 299.00,
            'currency' => 'BDT',
            'status' => 'sent',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->payment = Payment::create([
            'user_id' => $this->clientUser->id,
            'invoice_id' => $this->invoice->id,
            'transaction_id' => 'TXN-INITIAL-12345',
            'gateway' => 'bkash',
            'amount' => 299.00,
            'currency' => 'BDT',
            'status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function test_admin_can_view_payments_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.payments'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Payments/Index')
            ->has('payments')
            ->has('stats')
            ->has('gatewayStats')
            ->has('clients')
            ->has('unpaidInvoices')
        );
    }

    public function test_admin_can_record_manual_payment_and_settle_invoice(): void
    {
        $unsettledInvoice = Invoice::create([
            'user_id' => $this->clientUser->id,
            'subscription_id' => $this->subscription->id,
            'total_amount' => 450.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'paid_amount' => 0.00,
            'due_amount' => 450.00,
            'currency' => 'BDT',
            'status' => 'sent',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.payments.store'), [
                'user_id' => $this->clientUser->id,
                'invoice_id' => $unsettledInvoice->id,
                'gateway' => 'Nagad',
                'transaction_id' => 'NAGAD-TRX-778899',
                'amount' => 450.00,
                'currency' => 'BDT',
                'paid_at' => now()->toDateTimeString(),
                'notes' => 'Customer manual deposit',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->clientUser->id,
            'invoice_id' => $unsettledInvoice->id,
            'transaction_id' => 'NAGAD-TRX-778899',
            'gateway' => 'nagad',
            'amount' => 450.00,
            'status' => 'completed',
        ]);

        // Invoice should be fully settled as paid
        $this->assertEquals('paid', $unsettledInvoice->fresh()->status);
        $this->assertEquals(0, $unsettledInvoice->fresh()->due_amount);

        // Subscription should be auto-unsuspended to active
        $this->assertEquals('active', $this->subscription->fresh()->status);
    }

    public function test_admin_can_view_single_payment_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.billing.payments.show', $this->payment->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'payment' => [
                'id',
                'transaction_id',
                'gateway',
                'amount',
                'user',
                'invoice',
            ]
        ]);
    }

    public function test_admin_can_refund_payment(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.payments.refund', $this->payment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('refunded', $this->payment->fresh()->status);
        $this->assertNotNull($this->payment->fresh()->refunded_at);
    }

    public function test_admin_can_delete_pending_or_failed_payment(): void
    {
        $pendingPayment = Payment::create([
            'user_id' => $this->clientUser->id,
            'transaction_id' => 'TXN-FAILED-999',
            'gateway' => 'sslcommerz',
            'amount' => 100.00,
            'currency' => 'BDT',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.payments.destroy', $pendingPayment->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('payments', ['id' => $pendingPayment->id]);
    }

    public function test_admin_cannot_delete_completed_payment(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.payments.destroy', $this->payment->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('payments', ['id' => $this->payment->id]);
    }
}
