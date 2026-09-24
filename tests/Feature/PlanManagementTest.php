<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_hosting_packages_page(): void
    {
        HostingPlan::create([
            'name' => 'Starter Cloud',
            'slug' => 'starter-cloud',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 199.00,
            'price_yearly' => 1990.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.plans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Plans/Index')
            ->has('plans')
            ->has('stats')
        );
    }

    public function test_admin_can_create_hosting_package(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.store'), [
                'name' => 'Business Cloud Pro',
                'slug' => 'business-cloud-pro',
                'description' => 'Great for eCommerce',
                'disk_space' => 10240,
                'bandwidth' => 102400,
                'max_domains' => 5,
                'max_databases' => 5,
                'max_email_accounts' => 10,
                'php_version_default' => '8.5',
                'price_monthly' => 599.00,
                'price_yearly' => 5990.00,
                'auto_ssl' => true,
                'allow_ssh_access' => true,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('hosting_plans', [
            'name' => 'Business Cloud Pro',
            'slug' => 'business-cloud-pro',
            'disk_space' => 10240,
            'price_monthly' => 599.00,
        ]);
    }

    public function test_admin_can_update_hosting_package(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Basic Tier',
            'slug' => 'basic-tier',
            'disk_space' => 1024,
            'bandwidth' => 10240,
            'max_domains' => 1,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.plans.update', $plan->id), [
                'name' => 'Basic Tier Upgraded',
                'slug' => 'basic-tier',
                'disk_space' => 2048,
                'bandwidth' => 20480,
                'max_domains' => 2,
                'price_monthly' => 149.00,
                'price_yearly' => 1490.00,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('hosting_plans', [
            'id' => $plan->id,
            'name' => 'Basic Tier Upgraded',
            'disk_space' => 2048,
        ]);
    }

    public function test_admin_can_toggle_hosting_package_status(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Toggle Plan',
            'slug' => 'toggle-plan',
            'disk_space' => 1024,
            'bandwidth' => 10240,
            'max_domains' => 1,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.plans.toggle-status', $plan->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('hosting_plans', [
            'id' => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_unused_hosting_package(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Unused Plan',
            'slug' => 'unused-plan',
            'disk_space' => 1024,
            'bandwidth' => 10240,
            'max_domains' => 1,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.plans.destroy', $plan->id));

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseMissing('hosting_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_admin_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Active Sub Plan',
            'slug' => 'active-sub-plan',
            'disk_space' => 1024,
            'bandwidth' => 10240,
            'max_domains' => 1,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $group = ServerGroup::factory()->create();
        $server = Server::create([
            'name' => 'Node 1',
            'hostname' => 'node1.deeptouchhost.local',
            'ip_address' => '127.0.0.1',
            'server_group_id' => $group->id,
            'status' => 'online',
            'health_status' => 'healthy',
            'ssh_port' => 22,
            'ssh_user' => 'root',
        ]);

        Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'clientdomain.net',
            'username' => 'clientdomain',
            'document_root' => '/var/www/vhosts/clientdomain/clientdomain.net/public_html',
            'price' => 99.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.plans.destroy', $plan->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('hosting_plans', [
            'id' => $plan->id,
        ]);
    }
}
