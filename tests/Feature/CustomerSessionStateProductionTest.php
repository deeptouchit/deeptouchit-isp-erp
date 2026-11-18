<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantCustomer;
use App\Models\TenantRouter;
use App\Models\User;
use App\Services\Network\CustomerSessionStateService;
use App\Services\Network\MikrotikApiService;
use App\Services\Network\RadiusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSessionStateProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_source_failure_preserves_last_known_status(): void
    {
        [$tenant, $customer, $router] = $this->fixture('preserve', 'online');
        Schema::dropIfExists('radacct');
        $this->mock(MikrotikApiService::class)
            ->shouldReceive('getActivePppoe')->once()
            ->withArgs(fn (TenantRouter $candidate) => $candidate->id === $router->id)
            ->andReturn(['success' => false, 'message' => 'router unavailable']);

        $result = app(CustomerSessionStateService::class)->syncTenant($tenant);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame(0, $result['successful_sources']);
        $this->assertSame('online', $customer->fresh()->online_status);
        $this->assertNull($customer->fresh()->last_sync_at);
    }

    public function test_successful_source_updates_status_and_session_timestamps(): void
    {
        [$tenant, $onlineCustomer, $router] = $this->fixture('healthy', 'offline');
        $offlineCustomer = $this->customer($tenant, 'healthy-offline', 'online');
        $this->mock(MikrotikApiService::class)
            ->shouldReceive('getActivePppoe')->once()
            ->withArgs(fn (TenantRouter $candidate) => $candidate->id === $router->id)
            ->andReturn([
                'success' => true,
                'data' => [['name' => $onlineCustomer->username]],
            ]);

        $result = app(CustomerSessionStateService::class)->syncTenant($tenant);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['active_count']);
        $this->assertSame('online', $onlineCustomer->fresh()->online_status);
        $this->assertNotNull($onlineCustomer->fresh()->last_online_at);
        $this->assertNotNull($onlineCustomer->fresh()->last_sync_at);
        $this->assertSame('offline', $offlineCustomer->fresh()->online_status);
        $this->assertNotNull($offlineCustomer->fresh()->last_sync_at);
    }

    public function test_failed_kick_preserves_online_status(): void
    {
        [$tenant, $customer, $router] = $this->fixture('kick-fail', 'online');
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Session Admin',
            'email' => 'session-admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'isp_admin',
            'status' => 'active',
        ]);
        $this->mock(MikrotikApiService::class)
            ->shouldReceive('terminateActiveSession')->once()
            ->withArgs(fn (TenantRouter $candidate, string $username) =>
                $candidate->id === $router->id && $username === $customer->username)
            ->andReturn(['success' => false, 'message' => 'router failed']);
        $this->mock(RadiusService::class)
            ->shouldReceive('disconnectUser')->once()
            ->andReturn(['success' => false, 'message' => 'coa failed']);

        $this->actingAs($user)->postJson(route('tenant.customers.kick-session', $customer->id))
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('online_status', 'online');

        $this->assertSame('online', $customer->fresh()->online_status);
    }

    private function fixture(string $key, string $status): array
    {
        $tenant = Tenant::create([
            'name' => ucfirst($key) . ' ISP',
            'company_name' => ucfirst($key) . ' ISP',
            'slug' => $key . '-session-isp',
            'status' => 'active',
        ]);
        $router = TenantRouter::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst($key) . ' Router',
            'ip_address' => '192.0.2.10',
            'username' => 'admin',
            'password' => 'test-only',
            'radius_secret' => 'test-radius',
            'is_active' => true,
            'status' => 'online',
        ]);
        $customer = $this->customer($tenant, $key . '-user', $status, $router);

        return [$tenant, $customer, $router];
    }

    private function customer(Tenant $tenant, string $username, string $status, ?TenantRouter $router = null): TenantCustomer
    {
        return TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'router_id' => $router?->id,
            'customer_id' => strtoupper(substr($username, 0, 8)),
            'name' => ucfirst($username),
            'username' => $username,
            'password' => 'password',
            'phone' => '01700000000',
            'monthly_bill' => 500,
            'online_status' => $status,
            'status' => 'active',
        ]);
    }
}
