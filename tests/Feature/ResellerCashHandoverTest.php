<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantDailyCashHandover;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerCashHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_submit_once_and_own_admin_can_verify(): void
    {
        // The production admin handover UI already uses these columns.
        Schema::table('tenant_daily_cash_handovers', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->string('shift_type')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('manager_remarks')->nullable();
            $table->json('denominations')->nullable();
            $table->integer('total_receipts_count')->default(0);
            foreach (['system_collected_amount', 'handed_over_amount', 'shortage_amount', 'excess_amount', 'digital_collected_amount'] as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }
        });
        if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            Schema::table('tenant_customer_payments', fn (Blueprint $table) => $table->unsignedBigInteger('reseller_id')->nullable());
        }

        $tenant = Tenant::create(['name' => 'Handover ISP', 'company_name' => 'Handover ISP', 'slug' => 'handover-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Handovers Partner', 'mobile' => '01710000001']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000002']);
        $manager = $this->staff($reseller, 'reseller_manager');
        $admin = $this->staff($reseller, 'reseller_admin');
        $otherAdmin = $this->staff($other, 'reseller_admin');
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => 'HND-CUSTOMER',
            'name' => 'Cash Customer', 'username' => 'handover-customer', 'password' => 'test', 'phone' => '01810000001',
        ]);
        $receipt = DB::table('tenant_customer_payments')->insertGetId([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $customer->id,
            'invoice_no' => 'HND-RECEIPT', 'collected_by' => $manager->id, 'amount' => 100,
            'payment_method' => 'cash', 'status' => 'completed', 'paid_at' => now(),
        ]);

        $this->actingAs($manager)->get(route('reseller.finance.cash-handover'))->assertOk();
        $this->getJson(route('reseller.finance.cash-handover.collector-cash'))->assertOk()->assertJsonPath('receipts_count', 1);
        $payload = ['collector_id' => $manager->id, 'handover_date' => today()->toDateString(), 'shift_type' => 'daily', 'handed_over_amount' => 100];
        $id = $this->postJson(route('reseller.finance.cash-handover.store'), $payload)
            ->assertOk()->assertJsonPath('success', true)->json('handover_id');
        $this->assertDatabaseHas('tenant_customer_payments', ['id' => $receipt, 'handover_id' => $id]);
        $this->assertDatabaseHas('tenant_daily_cash_handovers', ['id' => $id, 'reseller_id' => $reseller->id, 'status' => 'pending']);
        $this->postJson(route('reseller.finance.cash-handover.store'), $payload)->assertUnprocessable();
        $this->postJson(route('reseller.finance.cash-handover.approve', $id))->assertForbidden();

        $this->actingAs($otherAdmin)->getJson(route('reseller.finance.cash-handover.show', $id))->assertNotFound();
        $this->postJson(route('reseller.finance.cash-handover.approve', $id))->assertNotFound();

        $this->actingAs($admin)->postJson(route('reseller.finance.cash-handover.approve', $id))->assertOk();
        $this->assertSame('approved', TenantDailyCashHandover::findOrFail($id)->status);
        $this->postJson(route('reseller.finance.cash-handover.reject', $id))->assertUnprocessable();
    }

    private function staff(TenantReseller $reseller, string $role): User
    {
        return User::factory()->create([
            'tenant_id' => $reseller->tenant_id, 'reseller_id' => $reseller->id, 'role' => $role, 'status' => 'active',
        ]);
    }
}
