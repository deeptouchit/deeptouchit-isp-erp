<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\NginxManager;
use App\Services\SSLManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected Subscription $subscription;

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
            'username' => 'subclient',
        ]);

        $plan = HostingPlan::create([
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
        $server = Server::create([
            'name' => 'Master Node',
            'hostname' => 'master.deeptouchhost.local',
            'ip_address' => '127.0.0.1',
            'server_group_id' => $group->id,
            'status' => 'online',
            'health_status' => 'healthy',
            'ssh_port' => 22,
            'ssh_user' => 'root',
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'deeptouchit.com',
            'username' => 'subclient',
            'document_root' => '/var/www/vhosts/subclient/deeptouchit.com/public_html',
            'php_version' => '8.5',
            'price' => 29.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_subdomains_directory(): void
    {
        Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'api.deeptouchit.com',
            'subdomain' => 'api',
            'document_root' => '/var/www/vhosts/subclient/api.deeptouchit.com/public_html',
            'php_version' => '8.5',
            'ssl_status' => 'active',
            'is_primary' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.hosting.subdomains'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Hosting/Subdomains/Index')
            ->has('subdomains.data')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_provision_new_subdomain(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.subdomains.store'), [
                'subscription_id' => $this->subscription->id,
                'subdomain_prefix' => 'blog',
                'php_version' => '8.5',
                'auto_ssl' => true,
            ]);

        $response->assertRedirect(route('admin.hosting.subdomains'));

        $this->assertDatabaseHas('websites', [
            'domain' => 'blog.deeptouchit.com',
            'subdomain' => 'blog',
            'subscription_id' => $this->subscription->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_issue_ssl_for_subdomain(): void
    {
        $subdomain = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'store.deeptouchit.com',
            'subdomain' => 'store',
            'document_root' => '/var/www/vhosts/subclient/store.deeptouchit.com/public_html',
            'php_version' => '8.5',
            'ssl_status' => 'none',
            'status' => 'active',
        ]);

        $this->mock(SSLManager::class, function ($mock) {
            $mock->shouldReceive('generateSSL')
                ->once()
                ->with('store.deeptouchit.com', $this->clientUser->email)
                ->andReturn(['success' => true]);
        });

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.subdomains.issue-ssl', $subdomain->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('websites', [
            'id' => $subdomain->id,
            'ssl_status' => 'active',
        ]);
    }

    public function test_admin_can_change_subdomain_php_version(): void
    {
        $subdomain = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'demo.deeptouchit.com',
            'subdomain' => 'demo',
            'document_root' => '/var/www/vhosts/subclient/demo.deeptouchit.com/public_html',
            'php_version' => '8.2',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.hosting.subdomains.change-php', $subdomain->id), [
                'php_version' => '8.5',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('websites', [
            'id' => $subdomain->id,
            'php_version' => '8.5',
        ]);
    }

    public function test_admin_can_toggle_subdomain_status(): void
    {
        $subdomain = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'qa.deeptouchit.com',
            'subdomain' => 'qa',
            'document_root' => '/var/www/vhosts/subclient/qa.deeptouchit.com/public_html',
            'php_version' => '8.5',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('suspendDomain')
                ->once()
                ->with('qa.deeptouchit.com')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.subdomains.toggle-status', $subdomain->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('websites', [
            'id' => $subdomain->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_delete_subdomain(): void
    {
        $subdomain = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'old.deeptouchit.com',
            'subdomain' => 'old',
            'document_root' => '/var/www/vhosts/subclient/old.deeptouchit.com/public_html',
            'php_version' => '8.5',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('deleteDomain')
                ->once()
                ->with('old.deeptouchit.com')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.hosting.subdomains.destroy', $subdomain->id));

        $response->assertRedirect(route('admin.hosting.subdomains'));
        $this->assertDatabaseMissing('websites', [
            'id' => $subdomain->id,
        ]);
    }
}
