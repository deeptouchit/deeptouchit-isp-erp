<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantGatewayTransaction;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerGatewayPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_gateway_details_and_exports_use_own_transactions(): void
    {
        // The live gateway module already uses this transaction schema.
        Schema::table('tenant_gateway_transactions', function (Blueprint $table) {
            $table->string('status')->change();
            $table->unsignedBigInteger('reseller_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->json('gateway_payload')->nullable();
            foreach (['transaction_id', 'gateway_trx_id', 'purpose', 'reference_id', 'payer_account', 'payer_name', 'status_message', 'ip_address'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        $tenant = Tenant::create(['name' => 'Gateway ISP', 'company_name' => 'Gateway ISP', 'slug' => 'gateway-isp', 'status' => 'active']);
        $own = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Gateway Partner', 'mobile' => '01710000001']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Another Partner', 'mobile' => '01710000002']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $own->id, 'role' => 'reseller_admin', 'status' => 'active',
        ]);

        foreach ([$own->id => 'OWN-GATEWAY', $other->id => 'OTHER-GATEWAY'] as $resellerId => $reference) {
            (new TenantGatewayTransaction)->forceFill([
                'tenant_id' => $tenant->id, 'reseller_id' => $resellerId, 'trx_id' => $reference,
                'transaction_id' => $reference, 'gateway' => 'bkash', 'purpose' => 'RESELLER_TOPUP',
                'status' => 'SUCCESS', 'amount' => 100, 'net_amount' => 100, 'gateway_payload' => ['internal' => 'test'],
            ])->save();
        }

        $this->actingAs($admin)->get(route('reseller.finance.gateway-transactions', ['period' => 'all']))->assertOk()
            ->assertViewHas('transactions', fn ($transactions) => $transactions->pluck('transaction_id')->all() === ['OWN-GATEWAY']);
        $ownId = TenantGatewayTransaction::where('transaction_id', 'OWN-GATEWAY')->value('id');
        $otherId = TenantGatewayTransaction::where('transaction_id', 'OTHER-GATEWAY')->value('id');
        $this->getJson(route('reseller.finance.gateway-transactions.show', $ownId))->assertOk()
            ->assertJsonPath('transaction.gateway_payload', []);
        $this->getJson(route('reseller.finance.gateway-transactions.show', $otherId))->assertNotFound();
        $csv = $this->get(route('reseller.finance.gateway-transactions.export', ['period' => 'all']))->assertOk()->streamedContent();
        $this->assertStringContainsString('OWN-GATEWAY', $csv);
        $this->assertStringNotContainsString('OTHER-GATEWAY', $csv);
    }
}
