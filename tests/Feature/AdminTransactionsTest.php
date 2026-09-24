<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected Transaction $transaction;

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

        $this->transaction = Transaction::create([
            'user_id' => $this->clientUser->id,
            'transaction_number' => 'TRX-20260901-ABC123',
            'type' => 'credit',
            'category' => 'invoice_payment',
            'description' => 'Initial Hosting Settlement',
            'amount' => 299.00,
            'currency' => 'BDT',
            'payment_method' => 'bkash',
            'gateway_reference' => 'BK-998877',
            'status' => 'completed',
            'transacted_at' => now(),
        ]);
    }

    public function test_admin_can_view_transactions_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.transactions'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Transactions/Index')
            ->has('transactions')
            ->has('stats')
            ->has('clients')
            ->has('invoices')
        );
    }

    public function test_admin_can_record_manual_transaction(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.transactions.store'), [
                'user_id' => $this->clientUser->id,
                'type' => 'adjustment',
                'category' => 'manual_adjustment',
                'description' => 'Bonus credit adjustment for account migration',
                'amount' => 150.00,
                'currency' => 'BDT',
                'payment_method' => 'manual',
                'gateway_reference' => 'REF-MIG-01',
                'status' => 'completed',
                'transacted_at' => now()->toDateTimeString(),
                'notes' => 'Approved by supervisor',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'adjustment',
            'amount' => 150.00,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_view_single_transaction_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.billing.transactions.show', $this->transaction->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'transaction' => [
                'id',
                'transaction_number',
                'type',
                'amount',
                'user',
            ]
        ]);
    }

    public function test_admin_can_update_transaction_notes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.billing.transactions.update', $this->transaction->id), [
                'description' => 'Updated Description',
                'category' => 'hosting_subscription',
                'notes' => 'Updated internal notes for audit',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'id' => $this->transaction->id,
            'description' => 'Updated Description',
            'notes' => 'Updated internal notes for audit',
        ]);
    }

    public function test_admin_can_delete_transaction(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.transactions.destroy', $this->transaction->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('transactions', ['id' => $this->transaction->id]);
    }
}
