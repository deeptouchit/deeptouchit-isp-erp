<?php

namespace Tests\Feature;

use App\Models\DomainAlias;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NginxManager;
use App\Services\SSLManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainAliasManagementTest extends TestCase
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
            'username' => 'aliasclient',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Unlimited Plan',
            'slug' => 'unlimited-plan',
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'max_domains' => 10,
            'price_monthly' => 49.00,
            'price_yearly' => 490.00,
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
            'username' => 'aliasclient',
            'document_root' => '/var/www/vhosts/aliasclient/deeptouchit.com/public_html',
            'php_version' => '8.5',
            'price' => 49.00,
            'next_billing_date' => now()->addYear(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_domain_aliases_directory(): void
    {
        DomainAlias::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'deeptouch.org',
            'target_type' => 'parked',
            'ssl_status' => 'active',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.hosting.aliases'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Hosting/Aliases/Index')
            ->has('aliases.data')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_provision_new_parked_domain_alias(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.aliases.store'), [
                'subscription_id' => $this->subscription->id,
                'domain' => 'mybrand.net',
                'target_type' => 'parked',
                'auto_ssl' => true,
            ]);

        $response->assertRedirect(route('admin.hosting.aliases'));

        $this->assertDatabaseHas('domain_aliases', [
            'domain' => 'mybrand.net',
            'subscription_id' => $this->subscription->id,
            'target_type' => 'parked',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_provision_redirect_domain_alias(): void
    {
        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.aliases.store'), [
                'subscription_id' => $this->subscription->id,
                'domain' => 'oldbrand.com',
                'target_type' => 'redirect',
                'redirect_url' => 'https://deeptouchit.com',
                'redirect_status_code' => 301,
                'auto_ssl' => true,
            ]);

        $response->assertRedirect(route('admin.hosting.aliases'));

        $this->assertDatabaseHas('domain_aliases', [
            'domain' => 'oldbrand.com',
            'target_type' => 'redirect',
            'redirect_url' => 'https://deeptouchit.com',
            'redirect_status_code' => 301,
        ]);
    }

    public function test_admin_can_issue_ssl_for_domain_alias(): void
    {
        $alias = DomainAlias::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'alias-ssl.com',
            'target_type' => 'parked',
            'ssl_status' => 'none',
            'status' => 'active',
        ]);

        $this->mock(SSLManager::class, function ($mock) {
            $mock->shouldReceive('generateSSL')
                ->once()
                ->with('alias-ssl.com', $this->clientUser->email)
                ->andReturn(['success' => true]);
        });

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('createVirtualHost')
                ->once()
                ->andReturn(['success' => true]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.aliases.issue-ssl', $alias->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('domain_aliases', [
            'id' => $alias->id,
            'ssl_status' => 'active',
        ]);
    }

    public function test_admin_can_toggle_domain_alias_status(): void
    {
        $alias = DomainAlias::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'toggle-alias.com',
            'target_type' => 'parked',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('suspendDomain')
                ->once()
                ->with('toggle-alias.com')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.hosting.aliases.toggle-status', $alias->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('domain_aliases', [
            'id' => $alias->id,
            'status' => 'suspended',
        ]);
    }

    public function test_admin_can_delete_domain_alias(): void
    {
        $alias = DomainAlias::create([
            'subscription_id' => $this->subscription->id,
            'domain' => 'delete-alias.com',
            'target_type' => 'parked',
            'status' => 'active',
        ]);

        $this->mock(NginxManager::class, function ($mock) {
            $mock->shouldReceive('deleteDomain')
                ->once()
                ->with('delete-alias.com')
                ->andReturn(true);
        });

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.hosting.aliases.destroy', $alias->id));

        $response->assertRedirect(route('admin.hosting.aliases'));
        $this->assertDatabaseMissing('domain_aliases', [
            'id' => $alias->id,
        ]);
    }
}
