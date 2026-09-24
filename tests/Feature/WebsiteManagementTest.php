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

class WebsiteManagementTest extends TestCase
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
            'username' => 'webclient',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 5,
            'price_monthly' => 199.00,
            'price_yearly' => 1990.00,
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
            'domain' => 'primarysite.com',
            'username' => 'webclient',
            'document_root' => '/var/www/vhosts/webclient/primarysite.com/public_html',
            'php_version' => '8.5',
            'price' => 199.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_websites_directory(): void
    {
        Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'primarysite.com',
            'document_root' => '/var/www/vhosts/webclient/primarysite.com/public_html',
            'php_version' => '8.5',
            'ssl_status' => 'active',
            'is_primary' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.websites.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Websites/Index')
            ->has('websites.data')
            ->has('stats')
            ->has('subscriptions')
            ->has('phpVersions')
        );
    }

    public function test_admin_can_deploy_new_domain_virtualhost(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.websites.store'), [
                'subscription_id' => $this->subscription->id,
                'domain' => 'seconddomain.org',
                'php_version' => '8.5',
                'auto_ssl' => true,
            ]);

        $response->assertRedirect(route('admin.websites.index'));

        $this->assertDatabaseHas('websites', [
            'domain' => 'seconddomain.org',
            'subscription_id' => $this->subscription->id,
            'php_version' => '8.5',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_issue_ssl_for_website(): void
    {
        $website = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'sslcheck.io',
            'document_root' => '/var/www/vhosts/webclient/sslcheck.io/public_html',
            'php_version' => '8.5',
            'ssl_status' => 'none',
            'status' => 'active',
        ]);

        $this->mock(SSLManager::class, function ($mock) {
            $mock->shouldReceive('generateSSL')
                ->once()
                ->with('sslcheck.io', $this->clientUser->email)
                ->andReturn(['success' => true]);
        });

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.websites.issue-ssl', $website->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('websites', [
            'id' => $website->id,
            'ssl_status' => 'active',
        ]);
    }

    public function test_admin_can_change_website_php_runtime(): void
    {
        $website = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'phpswitch.net',
            'document_root' => '/var/www/vhosts/webclient/phpswitch.net/public_html',
            'php_version' => '8.2',
            'ssl_status' => 'none',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.websites.change-php', $website->id), [
                'php_version' => '8.5',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('websites', [
            'id' => $website->id,
            'php_version' => '8.5',
        ]);
    }

    public function test_admin_can_toggle_website_status(): void
    {
        $website = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'toggleme.com',
            'document_root' => '/var/www/vhosts/webclient/toggleme.com/public_html',
            'php_version' => '8.5',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('suspendDomain')
                ->once()
                ->with('toggleme.com')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.websites.toggle-status', $website->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('websites', [
            'id' => $website->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_delete_website_virtualhost(): void
    {
        $website = Website::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'deleteme.xyz',
            'document_root' => '/var/www/vhosts/webclient/deleteme.xyz/public_html',
            'php_version' => '8.5',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('deleteDomain')
                ->once()
                ->with('deleteme.xyz')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.websites.destroy', $website->id));

        $response->assertRedirect(route('admin.websites.index'));

        $this->assertDatabaseMissing('websites', [
            'id' => $website->id,
        ]);
    }
}
