<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResellerBandwidthTest extends TestCase
{
    use RefreshDatabase;

    public function test_router_interface_counters_are_mapped_from_router_to_customer_direction(): void
    {
        $router = new TenantRouter(['ip_address' => '192.0.2.5']);
        $api = \Mockery::mock(MikrotikApiService::class)->makePartial();
        $api->shouldReceive('connect')->once()->andReturnTrue();
        $api->shouldReceive('writeCommand')->once()->with('/interface/pppoe-server/print', ['=.proplist=name,user', '?running=true'])->ordered();
        $api->shouldReceive('writeCommand')->once()->with('/interface/print', ['=.proplist=.id,name,rx-byte,tx-byte', '?type=pppoe-in'])->ordered();
        $api->shouldReceive('readAll')->twice()->andReturn(
            [['!re' => ['name' => 'static-binding', 'user' => 'customer']], ['!done' => []]],
            [
                ['!re' => ['.id' => '*1', 'name' => 'static-binding', 'rx-byte' => '250', 'tx-byte' => '900']],
                ['!re' => ['.id' => '*2', 'name' => 'other-interface', 'rx-byte' => '500', 'tx-byte' => '700']],
                ['!done' => []],
            ]
        );
        $api->shouldReceive('disconnect')->once();

        $this->assertSame([
            '*1:static-binding' => ['username' => 'customer', 'download' => 900, 'upload' => 250],
        ], $api->getPppoeSessionBytes($router));
    }

    public function test_it_only_displays_provisioned_reseller_bandwidth(): void
    {
        [$tenant, $reseller, $user] = $this->resellerAccount();
        $this->actingAs($user)->withSession(['locale' => 'en']);

        $this->get(route('reseller.bandwidth.index'))
            ->assertOk()
            ->assertSee('No bandwidth allocation configured.')
            ->assertDontSee('Subscriber Name');
        $this->assertDatabaseCount('tenant_reseller_bandwidth', 0);
        $this->getJson(route('reseller.bandwidth.traffic'))
            ->assertOk()->assertJsonPath('state', 'unconfigured')->assertJsonPath('current_download', null);

        TenantResellerBandwidth::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'notes' => 'Baseline dedicated wholesale pipeline',
            'interface_name' => 'ether1-wan', 'vlan_id' => 100, 'total_bandwidth_mbps' => 500,
        ]);
        $this->get(route('reseller.bandwidth.print'))->assertOk()->assertSee('No bandwidth allocation configured.');

        $this->actingAs(User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'isp_admin', 'status' => 'active']));
        $this->getJson(route('reseller.bandwidth.traffic'))->assertForbidden();
    }

    public function test_it_measures_only_the_assigned_queue_and_does_not_fake_missing_samples(): void
    {
        [$tenant, $reseller, $user] = $this->resellerAccount();
        $router = TenantRouter::create(['tenant_id' => $tenant->id, 'name' => 'Test Router', 'ip_address' => '192.0.2.5', 'is_active' => true]);
        $allocation = TenantResellerBandwidth::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'router_id' => $router->id,
            'mikrotik_queue_name' => 'partner-queue', 'total_bandwidth_mbps' => 100,
        ]);
        $key = 'reseller-bandwidth:' . hash('sha256', implode(':', [$tenant->id, $reseller->id, $allocation->id, $router->id, 'partner-queue']));
        $this->mock(MikrotikApiService::class, function ($mock) use ($router) {
            $mock->shouldReceive('getSimpleQueueBytes')
                ->withArgs(fn (TenantRouter $selected, string $queue) => $selected->is($router) && $queue === 'partner-queue')
                ->times(3)
                ->andReturn(['upload' => 200, 'download' => 300], ['upload' => 200, 'download' => 75000300], null);
        });

        $url = route('reseller.bandwidth.traffic');
        $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('state', 'sampling');
        $this->getJson($url)->assertOk()->assertJsonPath('current_download', null);
        $record = Cache::get($key);
        $record['checked_at'] -= 6;
        $record['counter']['time'] -= 6;
        Cache::put($key, $record, 120);

        $response = $this->getJson($url)->assertOk()->assertJsonPath('state', 'live')->assertJsonPath('current_upload', 0);
        $this->assertGreaterThan(90, $response->json('current_download'));
        $this->assertLessThanOrEqual(100, $response->json('current_download'));
        $this->assertEquals(round($response->json('current_download'), 1), $response->json('utilization_percent'));
        $this->withSession(['locale' => 'en'])->get(route('reseller.bandwidth.print'))->assertOk()->assertSee('Latest Bandwidth Measurement');
        $csv = $this->get(route('reseller.bandwidth.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Download Mbps', $csv);
        $this->assertStringNotContainsString('Subscriber Name', $csv);

        $record = Cache::get($key);
        $record['checked_at'] -= 6;
        Cache::put($key, $record, 120);
        $this->getJson($url)->assertOk()->assertJsonPath('state', 'unavailable')->assertJsonPath('current_download', null);
    }

    public function test_package_mode_totals_reseller_pppoe_sessions_across_routers(): void
    {
        [$tenant, $reseller, $user] = $this->resellerAccount('POSTPAID_MONTHLY');
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Partner', 'mobile' => '01710000212']);
        $first = TenantRouter::create(['tenant_id' => $tenant->id, 'name' => 'First Router', 'ip_address' => '192.0.2.5', 'is_active' => true]);
        $second = TenantRouter::create(['tenant_id' => $tenant->id, 'name' => 'Second Router', 'ip_address' => '192.0.2.6', 'is_active' => true]);
        foreach ([
            ['scope-one', $reseller->id, $first->id],
            ['scope-two', $reseller->id, $second->id],
            ['other-user', $other->id, $first->id],
        ] as [$username, $resellerId, $routerId]) {
            TenantCustomer::create([
                'tenant_id' => $tenant->id, 'reseller_id' => $resellerId, 'router_id' => $routerId,
                'name' => $username, 'username' => $username, 'customer_id' => $username,
                'phone' => '01810000025', 'password' => 'test', 'status' => 'active', 'connection_type' => 'pppoe',
            ]);
        }

        TenantResellerBandwidth::create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'router_id' => $first->id,
            'mikrotik_queue_name' => 'old-queue', 'total_bandwidth_mbps' => 100,
        ]);

        $session = fn ($username, $download, $upload) => compact('username', 'download', 'upload');
        $this->mock(MikrotikApiService::class, function ($mock) use ($first, $second, $session) {
            $mock->shouldReceive('getSimpleQueueBytes')->never();
            $mock->shouldReceive('getPppoeSessionBytes')->withArgs(fn ($router) => $router->is($first))->times(3)->andReturn(
                ['1' => $session('scope-one', 0, 0), '2' => $session('other-user', 0, 0)],
                ['1' => $session('scope-one', 15000000, 7500000), '2' => $session('other-user', 90000000, 90000000)],
                null,
            );
            $mock->shouldReceive('getPppoeSessionBytes')->withArgs(fn ($router) => $router->is($second))->times(3)->andReturn(
                ['1' => $session('scope-two', 0, 0)],
                ['1' => $session('scope-two', 22500000, 11250000)],
                ['1' => $session('scope-two', 45000000, 22500000)],
            );
        });

        $url = route('reseller.bandwidth.traffic');
        $this->actingAs($user)->withSession(['locale' => 'en']);
        $this->get(route('reseller.bandwidth.index'))->assertOk()
            ->assertSee('Active PPPoE Sessions')->assertDontSee('Bandwidth Allocation')->assertDontSee('scope-one');
        $this->getJson($url)->assertOk()->assertJsonPath('state', 'sampling')->assertJsonPath('active_sessions', 2)
            ->assertJsonPath('current_download', null);

        $ageSamples = function () use ($tenant, $first, $second) {
            foreach ([$first, $second] as $router) {
                $key = "reseller-pppoe-router:{$tenant->id}:{$router->id}";
                $entry = Cache::get($key);
                $entry['checked_at'] -= 6;
                Cache::put($key, $entry, 120);
            }
        };
        $ageSamples();

        $result = $this->getJson($url)->assertOk()->assertJsonPath('state', 'live')
            ->assertJsonPath('active_sessions', 2)->assertJsonPath('utilization_percent', null)
            ->assertDontSee('other-user')->assertDontSee('scope-one');
        $this->assertEqualsWithDelta(50, $result->json('current_download'), 4);
        $this->assertEqualsWithDelta(25, $result->json('current_upload'), 2);

        $ageSamples();
        $this->getJson($url)->assertOk()->assertJsonPath('state', 'partial')->assertJsonPath('active_sessions', 1);
        $this->get(route('reseller.bandwidth.print'))->assertOk()
            ->assertSee('Partial traffic measurement')->assertDontSee('scope-two');
        $this->postJson(route('reseller.bandwidth.request-upgrade'))->assertForbidden();
    }

    private function resellerAccount(string $billingType = 'BANDWIDTH_WHOLESALE'): array
    {
        $tenant = Tenant::create(['name' => 'Bandwidth ISP', 'company_name' => 'Bandwidth ISP', 'slug' => 'bandwidth-isp', 'status' => 'active']);
        if (!Schema::hasColumn('tenant_resellers', 'billing_type')) {
            Schema::table('tenant_resellers', fn (Blueprint $table) => $table->string('billing_type')->nullable());
        }
        $reseller = TenantReseller::create([
            'tenant_id' => $tenant->id, 'name' => 'Bandwidth Partner', 'mobile' => '01710000211', 'billing_type' => $billingType,
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'role' => 'reseller_admin', 'status' => 'active',
        ]);

        return [$tenant, $reseller, $user];
    }
}
