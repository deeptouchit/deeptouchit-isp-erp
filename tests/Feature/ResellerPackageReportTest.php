<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantInternetPackage;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerPackageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_catalog_is_shared_but_margin_and_exports_are_reseller_scoped(): void
    {
        // Mirror the catalog fields already used by the live network package pages.
        Schema::table('tenant_internet_packages', function (Blueprint $table) {
            foreach (['package_name', 'code', 'service_type'] as $column) {
                $table->string($column)->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->decimal('download_speed', 8, 2)->default(10);
        });
        if (!Schema::hasColumn('tenant_customers', 'package_id')) {
            Schema::table('tenant_customers', fn (Blueprint $table) => $table->unsignedBigInteger('package_id')->nullable());
        }

        $tenant = Tenant::create(['name' => 'Catalog ISP', 'company_name' => 'Catalog ISP', 'slug' => 'catalog-isp', 'status' => 'active']);
        $otherTenant = Tenant::create(['name' => 'Other ISP', 'company_name' => 'Other ISP', 'slug' => 'other-catalog-isp', 'status' => 'active']);
        $own = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Own Catalog', 'mobile' => '01710000011', 'commission_rate' => 25]);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Catalog', 'mobile' => '01710000012', 'commission_rate' => 25]);

        $package = $this->package($tenant, 'Catalog-37M', 37, true);
        $this->package($tenant, 'Catalog-10M', 10, true);
        $inactive = $this->package($tenant, 'Catalog-Inactive', 50, false);
        $foreign = $this->package($otherTenant, 'Foreign-Package', 50, true);
        foreach ([[$own, 'CUSTOM', 80], [$own, 'FREE', 0], [$other, 'OTHER', 600]] as [$reseller, $code, $bill]) {
            $customer = TenantCustomer::create([
                'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'customer_id' => $code,
                'name' => $code, 'username' => strtolower($code), 'password' => 'test', 'phone' => '01810000021',
                'status' => 'active', 'monthly_bill' => $bill,
            ]);
            DB::table('tenant_customers')->where('id', $customer->id)->update(['package_id' => $package]);
        }

        $filters = ['speed' => '50+', 'service_type' => 'PPPOE', 'search' => 'Catalog'];
        $this->actingAs($this->partner($own));
        foreach ([
            'bn' => ['খুচরা মূল্য', 'প্যাকেজের বিবরণ ও মূল্য'],
            'en' => ['Retail Rate', 'Package Specifications & Rates'],
        ] as $locale => [$rateLabel, $detailsLabel]) {
            $this->withSession(['locale' => $locale])->get(route('reseller.packages.index', $filters))
                ->assertOk()
                ->assertSee($rateLabel)
                ->assertSee($detailsLabel)
                ->assertSeeInOrder(['no-sort">#</th>', '<td class="text-center font-mono">1</td>', 'Catalog-37M</td>'], false);
        }

        foreach (['reseller.packages', 'reseller.margins'] as $group) {
            $result = $this->get(route("$group.index", $filters))->assertOk();
            $this->assertSame([$package], $result->viewData('packages')->pluck('id')->all());
            $this->assertSame(20.0, $result->viewData('stats')['total_monthly_profit']);
            $this->assertSame(2, $result->viewData('stats')['active_subscribers']);
            $this->getJson(route("$group.show", $inactive))->assertNotFound();
            $this->getJson(route("$group.show", $foreign))->assertNotFound();
            $this->get(route("$group.print", $filters))->assertOk()
                ->assertViewHas('packages', fn ($packages) => $packages->pluck('id')->all() === [$package]);
            $csv = $this->get(route("$group.export", $filters))->assertOk()->streamedContent();
            $this->assertStringContainsString('Catalog-37M', $csv);
            $this->assertStringNotContainsString('Catalog-10M', $csv);
        }

        $this->getJson(route('reseller.margins.show', $package))->assertOk()
            ->assertJsonPath('data.monthly_revenue', 80)->assertJsonPath('data.monthly_profit', 20);
        $this->get(route('reseller.margins.print', $filters))->assertOk()->assertViewHas('totalProfit', 20.0);
        $this->actingAs($this->partner($other))->getJson(route('reseller.margins.show', $package))->assertOk()
            ->assertJsonPath('data.monthly_profit', 150);
    }

    private function package(Tenant $tenant, string $profile, int $speed, bool $active): int
    {
        return DB::table((new TenantInternetPackage)->getTable())->insertGetId([
            'tenant_id' => $tenant->id, 'name' => $profile, 'mikrotik_profile' => $profile,
            'package_name' => $profile, 'service_type' => 'pppoe', 'price' => 100,
            'download_speed' => $speed, 'is_active' => $active,
        ]);
    }

    private function partner(TenantReseller $reseller): User
    {
        return User::factory()->create([
            'tenant_id' => $reseller->tenant_id, 'reseller_id' => $reseller->id,
            'role' => 'reseller_admin', 'status' => 'active',
        ]);
    }
}
