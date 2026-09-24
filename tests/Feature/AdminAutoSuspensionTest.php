<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutoSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Business Pro',
            'slug' => 'business-pro',
            'disk_space' => 20480,
            'bandwidth' => 204800,
            'price_monthly' => 19.99,
            'price_yearly' => 199.99,
            'is_active' => true,
        ]);

        $server = \App\Models\Server::create([
            'name' => 'Primary Node',
            'hostname' => 'primary.deeptouchhost.local',
            'ip_address' => '103.59.177.138',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $client->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'overdue-test.com',
            'username' => 'overduetest',
            'price' => 19.99,
            'status' => 'active',
            'next_billing_date' => now()->subDays(5),
            'expires_at' => now()->addDays(25),
        ]);
    }

    public function test_admin_can_view_auto_suspension_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.auto-suspension'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/AutoSuspension')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_fetch_auto_suspension_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.auto-suspension'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_subscriptions',
                'currently_suspended',
                'overdue_in_grace',
            ],
            'subscriptions',
        ]);
    }

    public function test_admin_can_suspend_unsuspend_and_extend_grace(): void
    {
        // Suspend
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-suspension.suspend', ['id' => $this->subscription->id]), [
                'reason' => 'Overdue payment',
            ]);

        $response->assertRedirect();
        $this->assertEquals('suspended', $this->subscription->fresh()->status);

        // Unsuspend
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-suspension.unsuspend', ['id' => $this->subscription->id]));

        $response->assertRedirect();
        $this->assertEquals('active', $this->subscription->fresh()->status);

        // Extend grace
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-suspension.extend-grace', ['id' => $this->subscription->id]), [
                'days' => 14,
            ]);

        $response->assertRedirect();
        $this->assertTrue($this->subscription->fresh()->next_billing_date->isFuture());
    }

    public function test_admin_can_run_auto_suspension_sweep(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.auto-suspension.sweep'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'suspended_count',
            'duration_ms',
        ]);
    }
}
