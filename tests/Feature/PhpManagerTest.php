<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Models\Subscription;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Services\PHP\PhpDiscoveryService;
use App\Services\PHP\PhpFpmService;
use App\Services\PHP\PhpConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_tester',
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'email' => 'admintester@deeptouchhost.local',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->client = User::create([
            'username' => 'client_tester',
            'first_name' => 'Client',
            'last_name' => 'Tester',
            'email' => 'clienttester@deeptouchhost.local',
            'password' => bcrypt('password'),
            'role' => 'client',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    public function test_unauthenticated_users_cannot_access_php_manager(): void
    {
        $response = $this->get(route('admin.php.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_client_users_cannot_access_admin_php_manager(): void
    {
        $this->actingAs($this->client);
        $response = $this->get(route('admin.php.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_php_manager_dashboard(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.php.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/Index')
            ->has('summary')
            ->has('discovered')
            ->has('directives')
            ->has('websites')
        );
    }

    public function test_discovery_service_detects_installed_versions(): void
    {
        $discovery = new PhpDiscoveryService();
        $discovered = $discovery->discoverInstalledVersions();

        $this->assertIsArray($discovered);
        $this->assertNotEmpty($discovered);
        $this->assertArrayHasKey('8.2', $discovered);
        $this->assertEquals('running', $discovered['8.2']['fpm_status']);
    }

    public function test_service_action_rejects_invalid_version_or_action(): void
    {
        $this->actingAs($this->admin);

        // Invalid version
        $response = $this->post(route('admin.php.service'), [
            'version' => 'invalid_version',
            'action' => 'reload',
        ]);
        $response->assertSessionHasErrors(['version']);

        // Invalid action
        $response = $this->post(route('admin.php.service'), [
            'version' => '8.2',
            'action' => 'destroy_all',
        ]);
        $response->assertSessionHasErrors(['action']);
    }

    public function test_configuration_update_rejects_invalid_sizes(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.php.config'), [
            'version' => '8.2',
            'settings' => [
                'memory_limit' => 'invalid_size_format_9999',
            ]
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_set_default_php_version(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.php.set-default'), [
            'version' => '8.3',
        ]);

        $response->assertRedirect();
        $this->assertEquals('8.3', \App\Models\SystemSetting::get('default_php_version'));
    }

    public function test_opcache_flush_triggers_graceful_reload(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.php.opcache-flush'), [
            'version' => '8.2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_compatibility_check_returns_valid_json(): void
    {
        $this->actingAs($this->admin);

        $plan = HostingPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'disk_space' => 5000,
            'bandwidth' => 50000,
            'max_domains' => 5,
            'max_databases' => 5,
            'max_ftp_accounts' => 5,
            'max_email_accounts' => 5,
            'price_monthly' => 9.99,
            'price_yearly' => 99.99,
            'is_active' => true,
        ]);

        $server = Server::create([
            'name' => 'Primary Node',
            'hostname' => 'node1.deeptouchhost.local',
            'ip_address' => '127.0.0.1',
            'status' => 'online',
        ]);

        $sub = Subscription::create([
            'user_id' => $this->client->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'mytestsite.com',
            'status' => 'active',
            'php_version' => '8.2',
            'period' => 'monthly',
            'price' => 9.99,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $site = Website::create([
            'subscription_id' => $sub->id,
            'domain' => 'mytestsite.com',
            'document_root' => '/var/www/vhosts/testuser/public_html',
            'php_version' => '8.2',
            'ssl_enabled' => false,
            'status' => 'active',
        ]);

        $response = $this->postJson(route('admin.php.compatibility'), [
            'website_id' => $site->id,
            'target_version' => '8.3',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'app_type', 'target_version', 'checks']);
    }

    public function test_repair_drift_endpoint(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.php.drift.repair'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
