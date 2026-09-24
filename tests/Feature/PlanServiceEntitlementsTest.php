<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanServiceEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $client;
    protected Server $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
        ]);

        $this->server = Server::factory()->create();
    }

    public function test_admin_can_create_hosting_plan_with_service_entitlements(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.plans.store'), [
            'name' => 'Enterprise Turbo Cloud',
            'slug' => 'enterprise-turbo-cloud',
            'description' => 'Flagship plan with Redis and Node.js support',
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'max_domains' => 10,
            'max_subdomains' => 20,
            'max_databases' => 10,
            'max_email_accounts' => 25,
            'max_ftp_accounts' => 10,
            'cpu_limit' => 200,
            'ram_limit' => 4096,
            'php_version_default' => '8.3',
            'price_monthly' => 999.00,
            'price_yearly' => 9990.00,
            'auto_ssl' => true,
            'allow_ssh_access' => true,
            'allow_git_deploy' => true,
            'allow_redis' => true,
            'redis_memory_mb' => 256,
            'allow_memcached' => true,
            'allow_nodejs' => true,
            'allow_python' => true,
            'allow_cron_jobs' => true,
            'allow_backups' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('hosting_plans', [
            'slug' => 'enterprise-turbo-cloud',
            'allow_redis' => true,
            'redis_memory_mb' => 256,
            'allow_memcached' => true,
            'allow_nodejs' => true,
            'allow_python' => true,
            'allow_cron_jobs' => true,
            'allow_backups' => true,
        ]);
    }

    public function test_admin_can_update_plan_service_entitlements(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Basic Starter',
            'slug' => 'basic-starter',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 199.00,
            'price_yearly' => 1990.00,
            'allow_redis' => false,
            'redis_memory_mb' => 64,
            'allow_memcached' => false,
            'allow_nodejs' => false,
            'allow_python' => false,
            'allow_cron_jobs' => true,
            'allow_backups' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.plans.update', $plan->id), [
            'name' => 'Basic Starter Upgraded',
            'slug' => 'basic-starter',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 249.00,
            'price_yearly' => 2490.00,
            'allow_redis' => true,
            'redis_memory_mb' => 128,
            'allow_memcached' => true,
            'allow_nodejs' => false,
            'allow_python' => false,
            'allow_cron_jobs' => true,
            'allow_backups' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('hosting_plans', [
            'id' => $plan->id,
            'name' => 'Basic Starter Upgraded',
            'allow_redis' => true,
            'redis_memory_mb' => 128,
            'allow_memcached' => true,
        ]);
    }

    public function test_client_cache_purge_fails_when_plan_does_not_permit_redis(): void
    {
        $plan = HostingPlan::create([
            'name' => 'No Redis Plan',
            'slug' => 'no-redis-plan',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'allow_redis' => false,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $this->client->id,
            'server_id' => $this->server->id,
            'plan_id' => $plan->id,
            'domain' => 'clientdomain.com',
            'username' => 'clientuser',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 99.00,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->client)->post(route('advanced.cache.purge-redis'));

        $response->assertSessionHas('error', 'Redis caching is not available on your current plan. Please upgrade your hosting package.');
    }
}
