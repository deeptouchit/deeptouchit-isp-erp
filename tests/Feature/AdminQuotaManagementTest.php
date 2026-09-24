<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuotaManagementTest extends TestCase
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

        $server = Server::factory()->create([
            'name' => 'Primary Node',
            'status' => 'online',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'disk_space' => 10000,
            'bandwidth' => 100000,
            'max_websites' => 5,
            'max_databases' => 5,
            'max_ftp_accounts' => 5,
            'max_email_accounts' => 5,
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->adminUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'test.deeptouchit.com',
            'username' => 'testuser',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 10,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_admin_can_view_quotas_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.quotas'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Quotas')
            ->has('quotas')
            ->has('stats')
        );
    }

    public function test_admin_can_update_subscription_quotas(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.quotas.update', $this->subscription->id), [
                'custom_disk_space' => 20480,
                'custom_inodes' => 500000,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->subscription->refresh();
        $this->assertEquals(20480, $this->subscription->custom_disk_space);
        $this->assertEquals(500000, $this->subscription->custom_inodes);
    }
}
