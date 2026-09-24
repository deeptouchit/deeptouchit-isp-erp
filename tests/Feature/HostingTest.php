<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\NginxManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected HostingPlan $plan;
    protected Server $server;

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
            'username' => 'hostclient',
        ]);

        $this->plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud',
            'disk_space' => 5120,
            'bandwidth' => 51200,
            'max_domains' => 5,
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'is_active' => true,
        ]);

        $group = ServerGroup::factory()->create();
        $this->server = Server::create([
            'name' => 'Main Node',
            'hostname' => 'node1.deeptouchhost.local',
            'ip_address' => '127.0.0.1',
            'server_group_id' => $group->id,
            'status' => 'online',
            'health_status' => 'healthy',
            'ssh_port' => 22,
            'ssh_user' => 'root',
        ]);
    }

    public function test_can_view_hosting_plans(): void
    {
        $this->assertDatabaseHas('hosting_plans', [
            'slug' => 'pro-cloud',
        ]);
    }

    public function test_authenticated_user_can_access_subscriptions(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('subscriptions.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_hosting_accounts_directory(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'domain' => 'testapp.com',
            'username' => 'testapp',
            'document_root' => '/var/www/vhosts/testapp/testapp.com/public_html',
            'php_version' => '8.5',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.hosting.accounts'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Hosting/Accounts/Index')
            ->has('accounts.data')
            ->has('stats')
            ->has('plans')
            ->has('servers')
            ->has('customers')
        );
    }

    public function test_admin_can_provision_new_hosting_account(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true, 'config_path' => '/etc/nginx/sites-available/newportal.org.conf']);
        });

        $response = $this->actingAs($this->adminUser)->post(route('admin.hosting.accounts.store'), [
            'user_id' => $this->clientUser->id,
            'domain' => 'newportal.org',
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'php_version' => '8.5',
            'period' => 'yearly',
        ]);

        $response->assertRedirect(route('admin.hosting.accounts'));

        $this->assertDatabaseHas('subscriptions', [
            'domain' => 'newportal.org',
            'user_id' => $this->clientUser->id,
            'status' => 'active',
            'php_version' => '8.5',
        ]);

        $this->assertDatabaseHas('websites', [
            'domain' => 'newportal.org',
            'php_version' => '8.5',
            'is_primary' => true,
        ]);
    }

    public function test_admin_can_toggle_hosting_account_suspension(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('suspendDomain')
                ->once()
                ->with('activeapp.io')
                ->andReturn(true);
        });

        $subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'domain' => 'activeapp.io',
            'username' => 'activeapp',
            'document_root' => '/var/www/vhosts/activeapp/activeapp.io/public_html',
            'php_version' => '8.5',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        Website::create([
            'subscription_id' => $subscription->id,
            'domain' => 'activeapp.io',
            'document_root' => '/var/www/vhosts/activeapp/activeapp.io/public_html',
            'php_version' => '8.5',
            'status' => 'active',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.accounts.toggle-status', $subscription->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_change_hosting_account_plan(): void
    {
        $newPlan = HostingPlan::create([
            'name' => 'Enterprise Turbo',
            'slug' => 'enterprise-turbo',
            'disk_space' => 20480,
            'bandwidth' => 204800,
            'max_domains' => 20,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'domain' => 'scaleup.com',
            'username' => 'scaleup',
            'document_root' => '/var/www/vhosts/scaleup/scaleup.com/public_html',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.hosting.accounts.change-plan', $subscription->id), [
                'plan_id' => $newPlan->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'plan_id' => $newPlan->id,
        ]);
    }

    public function test_admin_can_change_php_version(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'domain' => 'phpexample.com',
            'username' => 'phpexample',
            'document_root' => '/var/www/vhosts/phpexample/phpexample.com/public_html',
            'php_version' => '8.2',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        Website::create([
            'subscription_id' => $subscription->id,
            'domain' => 'phpexample.com',
            'document_root' => '/var/www/vhosts/phpexample/phpexample.com/public_html',
            'php_version' => '8.2',
            'status' => 'active',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.hosting.accounts.change-php', $subscription->id), [
                'php_version' => '8.5',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'php_version' => '8.5',
        ]);
        $this->assertDatabaseHas('websites', [
            'subscription_id' => $subscription->id,
            'php_version' => '8.5',
        ]);
    }

    public function test_admin_can_terminate_hosting_account(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('deleteDomain')
                ->once()
                ->with('terminateme.net')
                ->andReturn(true);
        });

        $subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $this->plan->id,
            'server_id' => $this->server->id,
            'domain' => 'terminateme.net',
            'username' => 'terminateme',
            'document_root' => '/var/www/vhosts/terminateme/terminateme.net/public_html',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.hosting.accounts.destroy', $subscription->id));

        $response->assertRedirect(route('admin.hosting.accounts'));
        $this->assertDatabaseMissing('subscriptions', [
            'id' => $subscription->id,
        ]);
    }
}
