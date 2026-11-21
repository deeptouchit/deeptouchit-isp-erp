<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantExpenseCategory;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_and_ledger_isolate_reseller_records_and_treat_wholesale_as_a_debit(): void
    {
        $this->installExistingFinanceColumns();
        $tenant = Tenant::create(['name' => 'Accounts ISP', 'company_name' => 'Accounts ISP', 'slug' => 'accounts-isp', 'status' => 'active']);
        $own = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Accounts Partner', 'mobile' => '01710000001']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000002']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $own->id, 'role' => 'reseller_admin', 'status' => 'active']);
        $otherAdmin = User::factory()->create(['tenant_id' => $tenant->id, 'reseller_id' => $other->id, 'role' => 'reseller_admin', 'status' => 'active']);
        $foreignCategory = TenantExpenseCategory::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $other->id, 'name' => 'Other Rent', 'type' => 'EXPENSE',
        ]);
        $category = $this->actingAs($admin)->postJson(route('reseller.finance.expenses.category'), [
            'name' => 'Office Rent', 'type' => 'EXPENSE',
        ])->assertOk()->json('category.id');
        $voucher = ['category_id' => $category, 'type' => 'EXPENSE', 'title' => 'Office Voucher', 'amount' => 25,
            'payment_method' => 'CASH', 'transaction_date' => today()->toDateString()];
        $id = $this->postJson(route('reseller.finance.expenses.store'), $voucher)->assertOk()->json('voucher.id');
        $this->assertDatabaseHas('tenant_expense_transactions', ['id' => $id, 'reseller_id' => $own->id]);
        $this->postJson(route('reseller.finance.expenses.store'), array_replace($voucher, ['category_id' => $foreignCategory->id]))->assertNotFound();
        $this->actingAs($otherAdmin)->getJson(route('reseller.finance.expenses.show', $id))->assertNotFound();

        foreach ([[$own, 'OWN', 100, 40], [$other, 'OTHER', 900, 300]] as [$reseller, $code, $collected, $paid]) {
            $customer = TenantCustomer::create([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $code,
                'name' => $code, 'username' => strtolower($code), 'password' => 'test', 'phone' => '01810000001',
            ]);
            DB::table('tenant_customer_payments')->insert([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $customer->id,
                'invoice_no' => "COL-$code", 'amount' => $collected, 'status' => 'completed',
                'paid_at' => now(), 'created_at' => now(), 'payment_method' => 'cash',
            ]);
            DB::table('tenant_reseller_invoices')->insert([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'invoice_no' => "WS-$code",
                'billing_month' => now()->format('Y-m'), 'paid_amount' => $paid, 'payment_status' => 'paid',
                'paid_at' => now(), 'payment_method' => 'bank',
            ]);
        }

        DB::table('tenant_daily_cash_handovers')->insert([
            'tenant_id' => $tenant->id, 'reseller_id' => $own->id, 'handover_no' => 'HND-ACCOUNTS',
            'collector_id' => $admin->id, 'handover_date' => today()->toDateString(),
            'status' => 'approved', 'handed_over_amount' => 100,
        ]);

        $this->actingAs($admin)->get(route('reseller.finance.expenses'))
            ->assertOk()->assertViewHas('grossTotalIncome', 100.0)->assertViewHas('totalExpenses', 65.0);
        $expenseCsv = $this->get(route('reseller.finance.expenses.export', ['type' => 'EXPENSE']))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString('Office Voucher', $expenseCsv);
        $this->assertStringContainsString('WS-OWN', $expenseCsv);
        $this->assertStringNotContainsString('COL-OWN', $expenseCsv);
        $this->assertStringNotContainsString('WS-OTHER', $expenseCsv);

        $ledger = $this->get(route('reseller.finance.ledger'))->assertOk();
        $this->assertSame(35.0, $ledger->viewData('kpi')['net_profit']);
        $this->assertSame(40.0, $ledger->viewData('pnl')['total_cogs']);
        $this->assertSame(40.0, $ledger->viewData('cashbook')['bank_out']);
        $this->assertSame(100.0, $ledger->viewData('cashbook')['approved_handovers']);
        $this->assertSame(35.0, $ledger->viewData('cashbook')['total_net']);
        $print = $this->get(route('reseller.finance.ledger.print', ['tab' => 'pnl']))->assertOk();
        $this->assertSame(35.0, $print->viewData('pnl')['net_profit']);
        $csv = $this->get(route('reseller.finance.ledger.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('WS-OWN', $csv);
        $this->assertStringNotContainsString('WS-OTHER', $csv);
    }

    private function installExistingFinanceColumns(): void
    {
        // Mirror the deployed admin accounting fields used by both portals.
        Schema::table('tenant_daily_cash_handovers', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
            $table->decimal('handed_over_amount', 12, 2)->default(0);
        });
        Schema::table('tenant_expense_categories', function (Blueprint $table) {
            $table->string('type')->change();
            $table->string('code')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('monthly_budget', 12, 2)->default(0);
        });
        Schema::table('tenant_expense_transactions', function (Blueprint $table) {
            $table->string('type')->change();
            $table->date('date')->nullable()->change();
            $table->date('transaction_date')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            foreach (['title', 'account_name', 'payee_payer', 'reference_no', 'status'] as $name) {
                $table->string($name)->nullable();
            }
        });
        if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            Schema::table('tenant_customer_payments', fn (Blueprint $table) => $table->unsignedBigInteger('reseller_id')->nullable());
        }
        if (!Schema::hasColumn('tenant_reseller_invoices', 'payment_method')) {
            Schema::table('tenant_reseller_invoices', fn (Blueprint $table) => $table->string('payment_method')->nullable());
        }
        if (!Schema::hasColumn('tenant_upstream_payments', 'paid_at')) {
            Schema::table('tenant_upstream_payments', fn (Blueprint $table) => $table->timestamp('paid_at')->nullable());
        }
    }
}
