<?php

namespace Tests\Feature;

use App\Models\ClientCredit;
use App\Models\HostingPlan;
use App\Models\Invoice;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCreditsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected Subscription $subscription;
    protected Invoice $invoice;

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
            'credit_balance' => 100.00,
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
            'domain' => 'creditsite.com',
            'username' => 'creditsite',
            'price' => 299.00,
            'status' => 'suspended',
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->invoice = Invoice::create([
            'user_id' => $this->clientUser->id,
            'subscription_id' => $this->subscription->id,
            'total_amount' => 200.00,
            'paid_amount' => 0.00,
            'due_amount' => 200.00,
            'currency' => 'BDT',
            'status' => 'sent',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function test_admin_can_view_credits_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.credits'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Credits/Index')
            ->has('credits')
            ->has('stats')
            ->has('clients')
            ->has('unpaidInvoices')
        );
    }

    public function test_admin_can_add_credit_to_client(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.credits.store'), [
                'user_id' => $this->clientUser->id,
                'type' => 'add',
                'amount' => 500.00,
                'currency' => 'BDT',
                'description' => 'Bank deposit credit top-up',
                'notes' => 'Cheque clearance confirmed',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(600.00, (float)$this->clientUser->fresh()->credit_balance);
        $this->assertDatabaseHas('client_credits', [
            'user_id' => $this->clientUser->id,
            'type' => 'add',
            'amount' => 500.00,
            'balance_before' => 100.00,
            'balance_after' => 600.00,
        ]);
    }

    public function test_admin_can_deduct_credit_from_client(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.credits.store'), [
                'user_id' => $this->clientUser->id,
                'type' => 'deduct',
                'amount' => 50.00,
                'currency' => 'BDT',
                'description' => 'Manual deduction for domain transfer fee',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(50.00, (float)$this->clientUser->fresh()->credit_balance);
    }

    public function test_admin_cannot_deduct_more_credit_than_client_balance(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.credits.store'), [
                'user_id' => $this->clientUser->id,
                'type' => 'deduct',
                'amount' => 999.00,
                'currency' => 'BDT',
                'description' => 'Excessive deduction',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Balance should remain unchanged
        $this->assertEquals(100.00, (float)$this->clientUser->fresh()->credit_balance);
    }

    public function test_admin_can_apply_credit_to_settle_invoice(): void
    {
        // First give user enough credit
        $this->clientUser->update(['credit_balance' => 300.00]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.credits.store'), [
                'user_id' => $this->clientUser->id,
                'invoice_id' => $this->invoice->id,
                'type' => 'auto_apply',
                'amount' => 200.00,
                'currency' => 'BDT',
                'description' => "Invoice settlement for {$this->invoice->invoice_no}",
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // User balance should be 100
        $this->assertEquals(100.00, (float)$this->clientUser->fresh()->credit_balance);

        // Invoice should be fully paid
        $this->assertEquals('paid', $this->invoice->fresh()->status);
        $this->assertEquals(0, $this->invoice->fresh()->due_amount);

        // Subscription should be auto-unsuspended to active
        $this->assertEquals('active', $this->subscription->fresh()->status);
    }

    public function test_admin_can_view_single_credit_json(): void
    {
        $credit = ClientCredit::create([
            'user_id' => $this->clientUser->id,
            'type' => 'add',
            'amount' => 50.00,
            'balance_before' => 50.00,
            'balance_after' => 100.00,
            'description' => 'Test',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.billing.credits.show', $credit->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'credit' => [
                'id',
                'type',
                'amount',
                'user',
            ]
        ]);
    }

    public function test_admin_can_revert_credit_transaction(): void
    {
        $credit = ClientCredit::create([
            'user_id' => $this->clientUser->id,
            'type' => 'add',
            'amount' => 50.00,
            'balance_before' => 50.00,
            'balance_after' => 100.00,
            'description' => 'Mistake Add',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.credits.destroy', $credit->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(50.00, (float)$this->clientUser->fresh()->credit_balance);
        $this->assertDatabaseMissing('client_credits', ['id' => $credit->id]);
    }
}
