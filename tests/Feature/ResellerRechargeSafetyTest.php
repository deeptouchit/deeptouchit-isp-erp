<?php

namespace Tests\Feature;

use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerRechargeSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mirror the deployed recharge fields consumed by the existing controllers.
        Schema::table('tenant_reseller_recharges', function (Blueprint $table) {
            $table->string('status')->change();
            $table->string('recharge_code')->nullable()->change();
            foreach (['recharge_no', 'gateway_trx_id', 'bank_name', 'bank_branch', 'bank_account_no', 'slip_path', 'rejection_reason'] as $name) {
                $table->string($name)->nullable();
            }
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('deposit_date')->nullable();
            $table->decimal('total_credited', 12, 2)->default(0);
        });
    }

    public function test_reference_submission_cannot_credit_wallet_and_approval_credits_only_once(): void
    {
        $tenant = Tenant::create(['name' => 'Wallet ISP', 'company_name' => 'Wallet ISP', 'slug' => 'wallet-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Wallet Partner', 'mobile' => '01710000001', 'wallet_balance' => 30]);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000002']);
        $partner = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'role' => 'reseller_admin', 'status' => 'active']);
        $otherPartner = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $other->id, 'role' => 'reseller_admin', 'status' => 'active']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'isp_admin', 'status' => 'active']);

        $payload = ['amount' => 50, 'gateway_channel' => 'BANK_GATEWAY', 'trx_id' => ' wallet-reference '];
        $result = $this->actingAs($partner)->postJson(route('reseller.recharge.online'), $payload)
            ->assertOk()->assertJsonPath('recharge.status', 'PENDING')->assertJsonPath('recharge.total_credited', '0.00');
        $id = $result->json('recharge.id');
        $this->assertSame(30.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 0);

        $this->actingAs($otherPartner)->postJson(route('reseller.recharge.online'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('gateway_trx_id');
        $this->getJson(route('reseller.recharge.receipt', $id))->assertNotFound();

        $this->actingAs($admin)->getJson(route('reseller.ledger.index'))->assertForbidden();
        $this->postJson(route('tenant.resellers.recharge.approve', $id))->assertOk();
        $this->postJson(route('tenant.resellers.recharge.approve', $id))->assertUnprocessable();
        $this->assertSame(80.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 1);
    }

    public function test_bkash_recovered_checkout_and_invoice_are_each_settled_once(): void
    {
        if (!Schema::hasColumn('tenant_reseller_invoices', 'payment_method')) {
            Schema::table('tenant_reseller_invoices', fn (Blueprint $table) => $table->string('payment_method')->nullable());
        }
        Schema::table('tenant_reseller_invoices', fn (Blueprint $table) => $table->string('payment_status')->change());

        $tenant = Tenant::create(['name' => 'Settlement ISP', 'company_name' => 'Settlement ISP', 'slug' => 'settlement-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Settlement Partner', 'mobile' => '01710000003', 'wallet_balance' => 100]);
        $otherReseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Settlement Partner', 'mobile' => '01710000004']);
        $partner = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'role' => 'reseller_admin', 'status' => 'active']);
        $otherPartner = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $otherReseller->id, 'role' => 'reseller_admin', 'status' => 'active']);
        foreach (['app_key', 'app_secret', 'username', 'password'] as $setting) {
            Setting::set('bkash_' . $setting, 'test-' . $setting, 'payment', $tenant->id);
        }

        $merchantInvoice = null;
        $amount = '49.00';
        Http::preventStrayRequests();
        Http::fake(function ($request) use (&$merchantInvoice, &$amount) {
            if (str_ends_with($request->url(), '/token/grant')) {
                return Http::response(['id_token' => 'test-token']);
            }
            if (str_ends_with($request->url(), '/checkout/create')) {
                $merchantInvoice = $request['merchantInvoiceNumber'];
                return Http::response(['statusCode' => '0000', 'paymentID' => 'TEST-PAY-ID', 'bkashURL' => 'https://example.test/checkout']);
            }
            if (str_ends_with($request->url(), '/checkout/execute')) {
                return Http::response(['statusCode' => 'ALREADY_EXECUTED'], 409);
            }
            if (str_ends_with($request->url(), '/payment/status')) {
                return Http::response([
                    'statusCode' => '0000', 'transactionStatus' => 'Completed', 'paymentID' => 'TEST-PAY-ID',
                    'merchantInvoiceNumber' => $merchantInvoice, 'trxID' => 'GATEWAY-TX-1', 'amount' => $amount, 'currency' => 'BDT',
                ]);
            }
            return Http::response([], 404);
        });
        $this->actingAs($partner)->postJson(route('reseller.recharge.bkash.initiate'), ['amount' => 50])->assertOk();
        $this->assertDatabaseHas('payment_transactions', ['transaction_reference' => 'reseller-bkash:TEST-PAY-ID', 'status' => 'pending']);
        $this->assertSame(0, PaymentTransaction::withoutWalletCheckouts()->count());
        $this->get(route('reseller.recharge.index'))->assertOk()
            ->assertViewHas('pendingCheckouts', fn ($checkouts) => $checkouts->count() === 1);
        $callback = route('reseller.recharge.bkash.callback', ['paymentID' => 'TEST-PAY-ID', 'status' => 'success']);
        $this->actingAs($otherPartner)->get($callback)->assertNotFound();
        $this->actingAs($partner)->get($callback)->assertRedirect()->assertSessionHas('error');
        $this->assertSame(100.0, (float) $reseller->fresh()->wallet_balance);
        $amount = '50.00';

        $this->get($callback)->assertRedirect()->assertSessionHas('success');
        $this->get($callback)->assertRedirect()->assertSessionHas('success');
        $this->assertSame('successful', PaymentTransaction::where('transaction_reference', 'reseller-bkash:TEST-PAY-ID')->value('status'));
        $this->assertSame(150.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_recharges', 1);

        $invoice = DB::table('tenant_reseller_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'invoice_no' => 'WS-WALLET-01',
            'billing_month' => now()->format('Y-m'), 'total_amount' => 120, 'due_amount' => 120, 'payment_status' => 'unpaid',
        ]);
        $anotherInvoice = DB::table('tenant_reseller_invoices')->insertGetId([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'invoice_no' => 'WS-WALLET-02',
            'billing_month' => now()->format('Y-m'), 'total_amount' => 120, 'due_amount' => 120, 'payment_status' => 'unpaid',
        ]);
        $this->actingAs($otherPartner)->postJson(route('reseller.invoices.pay-wallet', $invoice))->assertNotFound();
        $this->actingAs($partner)->postJson(route('reseller.invoices.pay-wallet', $invoice))->assertOk();
        $this->postJson(route('reseller.invoices.pay-wallet', $invoice))->assertUnprocessable();
        $this->postJson(route('reseller.invoices.pay-wallet', $anotherInvoice))->assertUnprocessable();
        $this->assertSame(30.0, (float) $reseller->fresh()->wallet_balance);
        $this->assertDatabaseCount('tenant_reseller_wallet_transactions', 2);

        $filters = ['type' => 'DEBIT', 'date_from' => today()->toDateString(), 'date_to' => today()->toDateString()];
        $this->get(route('reseller.ledger.index', $filters))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_debits'] === 120.0 && $stats['total_credits'] === 0.0);
        $this->get(route('reseller.ledger.print', $filters))->assertOk()
            ->assertViewHas('transactions', fn ($rows) => $rows->pluck('reference_no')->all() === ['WS-WALLET-01']);
        $csv = $this->get(route('reseller.ledger.export', $filters))->assertOk()->streamedContent();
        $this->assertStringContainsString('WS-WALLET-01', $csv);
        $this->assertStringNotContainsString('GATEWAY-TX-1', $csv);
    }
}
