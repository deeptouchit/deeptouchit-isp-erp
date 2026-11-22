<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantCustomerPayment;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerMonthlyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_csv_and_customer_links_share_the_reseller_scope(): void
    {
        // Mirror the columns already used by the live admin report.
        Schema::table('tenant_customers', function (Blueprint $table) {
            $table->string('zone')->nullable();
        });
        if (!Schema::hasColumn('tenant_customer_payments', 'reseller_id')) {
            Schema::table('tenant_customer_payments', fn (Blueprint $table) => $table->unsignedBigInteger('reseller_id')->nullable());
        }

        $this->travelTo(now()->setDate(2026, 11, 21));
        $tenant = Tenant::create([
            'name' => 'Analytics ISP',
            'company_name' => 'Analytics ISP',
            'slug' => 'analytics-isp',
            'status' => 'active',
        ]);
        $own = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Own Partner', 'mobile' => '01710000001']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000002']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $own->id,
            'role' => 'reseller_admin',
            'status' => 'active',
        ]);

        $paid = $this->customer($own, 'paid-customer', '2026-11-01');
        $unpaid = $this->customer($own, 'unpaid-customer', '2026-10-01');
        $foreign = $this->customer($other, 'other-customer', '2026-11-01');
        TenantCustomerPayment::create([
            'tenant_id' => $tenant->id, 'customer_id' => $paid->id, 'invoice_no' => 'AN-1',
            'reseller_id' => null, 'amount' => 125, 'paid_at' => '2026-11-10', 'status' => 'completed',
        ]);
        TenantCustomerPayment::create([
            'tenant_id' => $tenant->id, 'customer_id' => $foreign->id, 'invoice_no' => 'AN-2',
            'reseller_id' => $other->id, 'amount' => 999, 'paid_at' => '2026-11-10', 'status' => 'completed',
        ]);

        $filters = ['year' => 2026, 'scope' => 'all', 'reseller_id' => $other->id, 'zone' => 'Shared Zone'];
        $report = $this->actingAs($admin)->get(route('reseller.customers.monthly-analytics', $filters))->assertOk();
        $latest = $report->viewData('rows')[0];
        $this->assertSame('2026-11', $latest['month_key']);
        $this->assertSame(1, $latest['new_customers'], json_encode($latest));
        $this->assertSame(1, $latest['recharged_customers']);
        $this->assertSame(1, $latest['non_recharged_customers']);
        $this->assertSame(125.0, $latest['collection']);

        $csv = $this->get(route('reseller.customers.monthly-analytics', $filters + ['export' => 'csv']))
            ->assertOk()->assertDownload('monthly_customer_analytics_2026.csv')->streamedContent();
        $this->assertStringContainsString(',125,', $csv);
        $this->assertStringNotContainsString(',999,', $csv);

        foreach (['created_month' => $paid->id, 'recharged_month' => $paid->id, 'unpaid_month' => $unpaid->id] as $filter => $id) {
            $this->get(route('reseller.customers.index', [$filter => '2026-11', 'zone' => 'Shared Zone']))
                ->assertOk()->assertViewHas('customers', fn ($customers) => $customers->pluck('id')->all() === [$id]);
        }

        $collector = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $own->id, 'role' => 'reseller_collector', 'status' => 'active',
        ]);
        $this->actingAs($collector)->getJson(route('reseller.customers.monthly-analytics'))->assertForbidden();
    }

    private function customer(TenantReseller $reseller, string $code, string $createdAt): TenantCustomer
    {
        $customer = TenantCustomer::create([
            'tenant_id' => $reseller->tenant_id,
            'reseller_id' => $reseller->id,
            'customer_id' => $code,
            'name' => $code,
            'username' => $code,
            'password' => 'test-password',
            'phone' => '01810000001',
            'status' => 'active',
            'created_at' => $createdAt,
        ]);
        DB::table('tenant_customers')->where('id', $customer->id)->update(['zone' => 'Shared Zone']);

        return $customer;
    }
}
