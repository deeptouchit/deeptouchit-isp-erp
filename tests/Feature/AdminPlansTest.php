<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlansTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected HostingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->plan = HostingPlan::create([
            'name' => 'Starter Cloud NVMe',
            'slug' => 'starter-cloud-nvme',
            'description' => 'Perfect for entry-level workloads.',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'max_subdomains' => 5,
            'max_databases' => 2,
            'max_email_accounts' => 5,
            'max_ftp_accounts' => 2,
            'cpu_limit' => 100,
            'ram_limit' => 1024,
            'php_version_default' => '8.5',
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
            'auto_ssl' => true,
            'allow_ssh_access' => false,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_plans_index_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Plans/Index')
            ->has('plans')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_fetch_plans_live_api_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.plans.api.metrics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'plans',
            'stats' => [
                'total_plans',
                'active_plans',
                'total_subscribers',
                'avg_price',
            ],
        ]);
    }

    public function test_admin_can_create_hosting_plan(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.store'), [
                'name' => 'Business Pro Enterprise',
                'slug' => 'business-pro-enterprise',
                'description' => 'High resource quota tier.',
                'disk_space' => 10240,
                'bandwidth' => 102400,
                'max_domains' => 5,
                'max_subdomains' => 20,
                'max_databases' => 10,
                'max_email_accounts' => 25,
                'max_ftp_accounts' => 10,
                'cpu_limit' => 200,
                'ram_limit' => 4096,
                'php_version_default' => '8.5',
                'price_monthly' => 899.00,
                'price_yearly' => 8990.00,
                'auto_ssl' => true,
                'allow_ssh_access' => true,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hosting_plans', [
            'name' => 'Business Pro Enterprise',
            'slug' => 'business-pro-enterprise',
            'disk_space' => 10240,
            'price_monthly' => 899.00,
        ]);
    }

    public function test_admin_can_update_hosting_plan(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.plans.update', $this->plan->id), [
                'name' => 'Starter Cloud NVMe Updated',
                'slug' => 'starter-cloud-nvme',
                'description' => 'Updated tier description.',
                'disk_space' => 4096,
                'bandwidth' => 40960,
                'max_domains' => 2,
                'max_subdomains' => 10,
                'max_databases' => 5,
                'max_email_accounts' => 10,
                'max_ftp_accounts' => 5,
                'cpu_limit' => 150,
                'ram_limit' => 2048,
                'php_version_default' => '8.5',
                'price_monthly' => 399.00,
                'price_yearly' => 3990.00,
                'auto_ssl' => true,
                'allow_ssh_access' => true,
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hosting_plans', [
            'id' => $this->plan->id,
            'name' => 'Starter Cloud NVMe Updated',
            'disk_space' => 4096,
            'price_monthly' => 399.00,
        ]);
    }

    public function test_admin_can_toggle_plan_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.toggle-status', $this->plan->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->plan->fresh()->is_active);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.toggle-status', $this->plan->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->plan->fresh()->is_active);
    }

    public function test_admin_can_clone_hosting_plan(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.clone', $this->plan->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hosting_plans', [
            'name' => 'Starter Cloud NVMe (Copy)',
            'disk_space' => $this->plan->disk_space,
            'price_monthly' => $this->plan->price_monthly,
        ]);
    }

    public function test_admin_can_delete_unsubscribed_plan(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.plans.destroy', $this->plan->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('hosting_plans', ['id' => $this->plan->id]);
    }

    public function test_admin_cannot_delete_plan_with_active_subscriptions(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $server = Server::create([
            'name' => 'Node 1',
            'hostname' => 'node1.deeptouchhost.local',
            'ip_address' => '103.59.177.138',
            'port' => 22,
            'username' => 'root',
            'status' => 'online',
        ]);

        Subscription::create([
            'user_id' => $client->id,
            'plan_id' => $this->plan->id,
            'server_id' => $server->id,
            'domain' => 'subscribed-domain.com',
            'username' => 'subdomainuser',
            'price' => 299.00,
            'status' => 'active',
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.plans.destroy', $this->plan->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('hosting_plans', ['id' => $this->plan->id]);
    }
}
