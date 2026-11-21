<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerCoverageZoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Match the existing Coverage Zones schema used by the admin portal.
        Schema::table('tenant_coverage_zones', function (Blueprint $table) {
            $table->string('city_upazila', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('in_charge_name', 150)->nullable();
            $table->string('in_charge_phone', 50)->nullable();
        });

        Schema::table('tenant_customers', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->constrained('tenant_coverage_zones')->nullOnDelete();
            $table->string('zone', 150)->nullable();
        });
    }

    public function test_reseller_can_only_view_and_manage_own_coverage_zones(): void
    {
        $tenant = Tenant::create(['name' => 'Zone ISP', 'company_name' => 'Zone ISP', 'slug' => 'zone-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Local Partner', 'mobile' => '01710000001']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000002']);
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $reseller->id,
            'role' => 'reseller_admin',
            'status' => 'active',
        ]);

        $createZone = fn (TenantReseller $owner, string $name, string $code) => TenantCoverageZone::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $owner->id,
            'name' => $name,
            'code' => $code,
        ]);

        $ownZone = $createZone($reseller, 'Shared Area', 'LOCAL');
        $otherZone = $createZone($other, 'Shared Area', 'REMOTE');
        TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $other->id,
            'customer_id' => 'ZONE-001',
            'name' => 'Other Subscriber',
            'username' => 'zone-test',
            'password' => 'test-password',
            'phone' => '01810000001',
            'zone' => $otherZone->name,
            'monthly_bill' => 500,
        ]);

        $this->actingAs($admin)->get(route('reseller.customers.zones', [
            'reseller_id' => $other->id,
            'occupancy' => 'empty',
        ]))->assertOk()
            ->assertViewHas('zones', fn ($zones) => $zones->pluck('id')->all() === [$ownZone->id])
            ->assertViewHas('zoneMetrics', fn ($metrics) => $metrics[$ownZone->id]['subscribers_count'] === 0)
            ->assertDontSee('REMOTE');

        $this->putJson(route('reseller.customers.zones.update', $otherZone), ['name' => 'Outside Scope'])
            ->assertNotFound();

        $csv = UploadedFile::fake()->createWithContent('zones.csv', "name,code\nOutside Scope,REMOTE\nImported Area,LOCAL-2\n");
        $this->postJson(route('reseller.customers.zones.import'), [
            'file' => $csv,
            'duplicate_action' => 'update',
            'default_reseller_id' => $other->id,
        ])->assertOk();

        $this->assertSame('Shared Area', $otherZone->fresh()->name);
        $this->assertDatabaseHas('tenant_coverage_zones', ['code' => 'LOCAL-2', 'reseller_id' => $reseller->id]);

        $created = $this->postJson(route('reseller.customers.zones.store'), [
            'name' => 'New Area',
            'reseller_id' => $other->id,
        ])->assertOk()->assertJsonPath('zone.reseller_id', $reseller->id)->json('zone.id');

        $this->putJson(route('reseller.customers.zones.update', $created), [
            'name' => 'Renamed Area',
            'code' => 'NEW-AREA',
        ])->assertOk()->assertJsonPath('zone.name', 'Renamed Area');
        $this->postJson(route('reseller.customers.zones.sub-areas', $created), [
            'sub_areas' => ['East', 'West'],
        ])->assertOk()->assertJsonPath('sub_areas', ['East', 'West']);
        $this->postJson(route('reseller.customers.zones.toggle-status', $created))
            ->assertOk()->assertJsonPath('is_active', false);
        $this->get(route('reseller.customers.zones.import-template'))->assertOk();

        $collector = User::factory()->create([
            'tenant_id' => $tenant->id,
            'reseller_id' => $reseller->id,
            'role' => 'reseller_collector',
            'status' => 'active',
        ]);
        $this->actingAs($collector)->get(route('reseller.customers.zones'))
            ->assertOk()->assertDontSee('Add New Zone');
        $this->postJson(route('reseller.customers.zones.store'), ['name' => 'Forbidden Area'])
            ->assertForbidden();

        $this->actingAs($admin)->deleteJson(route('reseller.customers.zones.destroy', $ownZone))->assertOk();
        $this->assertModelMissing($ownZone);
        $this->assertModelExists($otherZone);
    }
}
