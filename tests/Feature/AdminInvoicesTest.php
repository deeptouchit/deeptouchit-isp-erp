<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoicesTest extends TestCase
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
            'domain' => 'clientdomain.com',
            'username' => 'clientdomain',
            'price' => 299.00,
            'status' => 'suspended',
            'next_billing_date' => now()->subDays(2),
            'expires_at' => now()->subDays(2),
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
            'notes' => 'Standard monthly hosting invoice',
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Starter Cloud NVMe - Monthly',
            'quantity' => 1,
            'unit_price' => 299.00,
            'total_price' => 299.00,
            'tax_rate' => 0.00,
        ]);
    }

    public function test_admin_can_view_invoices_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.invoices'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Invoices/Index')
            ->has('invoices')
            ->has('stats')
            ->has('clients')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_generate_manual_invoice_with_items(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.invoices.store'), [
                'user_id' => $this->clientUser->id,
                'subscription_id' => $this->subscription->id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'tax_amount' => 15.00,
                'discount_amount' => 10.00,
                'currency' => 'BDT',
                'notes' => 'Custom upgrade charge',
                'items' => [
                    [
                        'description' => 'Dedicated IP Address',
                        'quantity' => 2,
                        'unit_price' => 150.00,
                    ],
                    [
                        'description' => 'Premium SSL Add-on',
                        'quantity' => 1,
                        'unit_price' => 200.00,
                    ]
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Subtotal = (2*150) + 200 = 500, Tax = 15, Discount = 10 -> Total = 505
        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->clientUser->id,
            'total_amount' => 505.00,
            'due_amount' => 505.00,
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_view_single_invoice_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.billing.invoices.show', $this->invoice->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'invoice' => [
                'id',
                'invoice_no',
                'total_amount',
                'user',
                'items',
                'payments',
            ]
        ]);
    }

    public function test_admin_can_mark_invoice_as_paid_and_unsuspend_subscription(): void
    {
        $this->assertEquals('suspended', $this->subscription->status);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.invoices.mark-paid', $this->invoice->id), [
                'gateway' => 'bKash',
                'transaction_id' => 'BKASH-TRX998877',
                'amount' => 299.00,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'status' => 'paid',
            'paid_amount' => 299.00,
            'due_amount' => 0.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'gateway' => 'bkash',
            'transaction_id' => 'BKASH-TRX998877',
            'status' => 'completed',
        ]);

        // Subscription should now be auto-unsuspended to active!
        $this->assertEquals('active', $this->subscription->fresh()->status);
    }

    public function test_admin_can_send_invoice_reminder(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.invoices.send-reminder', $this->invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_cancel_invoice(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.invoices.cancel', $this->invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('cancelled', $this->invoice->fresh()->status);
    }

    public function test_admin_can_delete_unpaid_invoice(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.invoices.destroy', $this->invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('invoices', ['id' => $this->invoice->id]);
    }

    public function test_admin_cannot_delete_paid_invoice(): void
    {
        $this->invoice->update([
            'status' => 'paid',
            'paid_amount' => 299.00,
            'due_amount' => 0.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.invoices.destroy', $this->invoice->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('invoices', ['id' => $this->invoice->id]);
    }
}
