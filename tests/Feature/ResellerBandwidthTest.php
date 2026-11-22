<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\TenantResellerBandwidth;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Network\MikrotikApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResellerBandwidthTest extends TestCase
{
    use RefreshDatabase;

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

    private function resellerAccount(): array
    {
        $tenant = Tenant::create(['name' => 'Bandwidth ISP', 'company_name' => 'Bandwidth ISP', 'slug' => 'bandwidth-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Bandwidth Partner', 'mobile' => '01710000211']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $reseller->id, 'role' => 'reseller_admin', 'status' => 'active',
        ]);

        return [$tenant, $reseller, $user];
    }
}
