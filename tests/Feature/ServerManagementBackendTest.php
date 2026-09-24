<?php

namespace Tests\Feature;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerHealthStatus;
use App\Enums\Infrastructure\ServerStatus;
use App\Enums\Infrastructure\ServerType;
use App\Jobs\Infrastructure\Servers\DiscoverServerJob;
use App\Jobs\Infrastructure\Servers\SyncServerJob;
use App\Jobs\Infrastructure\Servers\VerifyServerJob;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerManagementBackendTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@deeptouchhost.local',
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'email' => 'client@deeptouchhost.local',
        ]);
    }

    public function test_admin_can_view_servers_index_with_search_and_filters(): void
    {
        $group = ServerGroup::factory()->create(['name' => 'Europe DC1']);
        $serverA = Server::factory()->create([
            'name' => 'Web Edge Alpha',
            'hostname' => 'alpha.deeptouchhost.io',
            'ip_address' => '10.0.1.10',
            'status' => ServerStatus::ACTIVE,
            'server_group_id' => $group->id,
        ]);
        $serverB = Server::factory()->create([
            'name' => 'Database Beta',
            'hostname' => 'beta.deeptouchhost.io',
            'ip_address' => '10.0.1.20',
            'status' => ServerStatus::MAINTENANCE,
        ]);

        // 1. View index
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Servers/Index')
                ->has('servers.data', 2)
            );

        // 2. Search filter
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.index', ['search' => 'Alpha']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Servers/Index')
                ->has('servers.data', 1)
                ->where('servers.data.0.id', $serverA->id)
            );

        // 3. Status filter
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.index', ['status' => 'maintenance']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Servers/Index')
                ->has('servers.data', 1)
                ->where('servers.data.0.id', $serverB->id)
            );
    }

    public function test_client_is_forbidden_from_viewing_admin_servers(): void
    {
        $this->actingAs($this->clientUser)
            ->get(route('admin.servers.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_server_and_queues_verification(): void
    {
        Queue::fake();

        $group = ServerGroup::factory()->create();

        $payload = [
            'name' => 'Edge Worker 02',
            'hostname' => 'edge02.deeptouchhost.io',
            'ip_address' => '192.168.10.50',
            'server_group_id' => $group->id,
            'server_type' => ServerType::WORKER->value,
            'environment' => ServerEnvironment::PRODUCTION->value,
            'ssh_port' => 2222,
            'ssh_user' => 'deploy',
            'auth_type' => ServerAuthType::PASSWORD->value,
            'ssh_password' => 'SecretPassword123!',
            'ssh_host_key_policy' => 'tofu',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.servers.store'), $payload);

        $server = Server::where('hostname', 'edge02.deeptouchhost.io')->first();
        $this->assertNotNull($server);
        $this->assertEquals('Edge Worker 02', $server->name);
        $this->assertEquals(2222, $server->ssh_port);
        $this->assertEquals('deploy', $server->ssh_user);
        $this->assertEquals(ServerStatus::PENDING, $server->status);

        $response->assertRedirect(route('admin.servers.show', $server->id));
        Queue::assertPushed(VerifyServerJob::class);
    }

    public function test_server_store_validation_rejects_invalid_inputs(): void
    {
        $this->actingAs($this->adminUser)
            ->postJson(route('admin.servers.store'), [
                'name' => '',
                'hostname' => 'invalid hostname with spaces!',
                'ip_address' => 'not-an-ip',
                'ssh_port' => 999999, // out of range
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'hostname', 'ip_address', 'ssh_port']);
    }

    public function test_admin_can_update_server_metadata(): void
    {
        $server = Server::factory()->create([
            'name' => 'Old Name',
            'hostname' => 'node.old.com',
            'ip_address' => '10.0.0.1',
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.servers.update', $server->id), [
                'name' => 'New Name',
                'hostname' => 'node.new.com',
                'ip_address' => '10.0.0.2',
            ])
            ->assertRedirect(route('admin.servers.show', $server->id));

        $fresh = $server->fresh();
        $this->assertEquals('New Name', $fresh->name);
        $this->assertEquals('node.new.com', $fresh->hostname);
        $this->assertEquals('10.0.0.2', $fresh->ip_address);
    }

    public function test_server_delete_safeguards_master_node(): void
    {
        $master = Server::factory()->create(['is_master' => true]);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.servers.destroy', $master->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('servers', ['id' => $master->id]);
    }

    public function test_server_delete_safeguards_active_hosting_accounts(): void
    {
        $server = Server::factory()->create(['is_master' => false]);
        $plan = HostingPlan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter-plan',
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
            'is_active' => true,
        ]);
        
        Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'testsite.com',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'price' => 299.00,
            'starts_at' => now(),
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.servers.destroy', $server->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('servers', ['id' => $server->id]);
    }

    public function test_admin_can_trigger_verify_discover_sync_and_maintenance(): void
    {
        Queue::fake();

        $server = Server::factory()->create(['status' => ServerStatus::ACTIVE]);

        // 1. Verify
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.verify', $server->id))
            ->assertSessionHas('success');
        Queue::assertPushed(VerifyServerJob::class);

        // 2. Discover
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.discover', $server->id))
            ->assertSessionHas('success');
        Queue::assertPushed(DiscoverServerJob::class);

        // 3. Sync
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.sync', $server->id))
            ->assertSessionHas('success');
        Queue::assertPushed(SyncServerJob::class);

        // 4. Maintenance
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.maintenance', $server->id), [
                'enabled' => true,
                'reason' => 'Kernel Upgrade',
            ])
            ->assertSessionHas('success');

        $this->assertEquals(ServerStatus::MAINTENANCE, $server->fresh()->status);
        $this->assertEquals('Kernel Upgrade', $server->fresh()->maintenance_reason);
    }

    public function test_admin_can_manage_allowlisted_services_and_rejects_untrusted(): void
    {
        $server = Server::factory()->create(['status' => ServerStatus::ACTIVE]);

        // 1. Allowlisted service action
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.services', $server->id), [
                'service' => 'nginx',
                'action' => 'restart',
            ])
            ->assertSessionHas('success');

        // 2. Untrusted service rejected by form validation
        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.services', $server->id), [
                'service' => 'malicious_daemon',
                'action' => 'restart',
            ])
            ->assertSessionHasErrors(['service']);
    }

    public function test_admin_can_approve_ssh_host_key_fingerprint(): void
    {
        $server = Server::factory()->create([
            'trusted_ssh_host_key_fingerprint' => null,
            'ssh_host_key_fingerprint' => 'SHA256:discovered_key_hash_123',
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.servers.host-key.approve', $server->id), [
                'fingerprint' => 'SHA256:discovered_key_hash_123',
                'reason' => 'Verified on server console',
            ])
            ->assertSessionHas('success');

        $this->assertEquals('SHA256:discovered_key_hash_123', $server->fresh()->trusted_ssh_host_key_fingerprint);
    }

    public function test_server_rest_api_collection_and_detail_does_not_leak_secrets(): void
    {
        Sanctum::actingAs($this->adminUser);

        $server = Server::factory()->create([
            'name' => 'API Node Alpha',
            'encrypted_ssh_password' => 'SuperSecret123!',
            'encrypted_ssh_key' => 'Private-Key-Content',
            'agent_token' => 'sh_agt_secret_token',
        ]);

        // 1. List API
        $response = $this->getJson('/api/v1/servers');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'uuid', 'name', 'hostname', 'ip_address', 'status', 'health_status', 'ssh_port', 'auth_type']
                ]
            ]);

        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('SuperSecret123!', $responseContent);
        $this->assertStringNotContainsString('Private-Key-Content', $responseContent);
        $this->assertStringNotContainsString('sh_agt_secret_token', $responseContent);

        // 2. Detail API
        $detailResponse = $this->getJson("/api/v1/servers/{$server->id}");
        $detailResponse->assertStatus(200);

        $detailContent = $detailResponse->getContent();
        $this->assertStringNotContainsString('SuperSecret123!', $detailContent);
        $this->assertStringNotContainsString('Private-Key-Content', $detailContent);
        $this->assertStringNotContainsString('sh_agt_secret_token', $detailContent);
    }

    public function test_server_rest_api_async_operations(): void
    {
        Queue::fake();
        Sanctum::actingAs($this->adminUser);

        $server = Server::factory()->create();

        // 1. Verify
        $this->postJson("/api/v1/servers/{$server->id}/verify")
            ->assertStatus(202)
            ->assertJson(['success' => true]);
        Queue::assertPushed(VerifyServerJob::class);

        // 2. Discover
        $this->postJson("/api/v1/servers/{$server->id}/discover")
            ->assertStatus(202)
            ->assertJson(['success' => true]);
        Queue::assertPushed(DiscoverServerJob::class);

        // 3. Sync
        $this->postJson("/api/v1/servers/{$server->id}/sync")
            ->assertStatus(202)
            ->assertJson(['success' => true]);
        Queue::assertPushed(SyncServerJob::class);
    }

    public function test_admin_can_render_server_create_edit_and_show_pages(): void
    {
        $server = Server::factory()->create([
            'name' => 'Edge Worker Alpha',
            'hostname' => 'alpha.deeptouchhost.io',
        ]);

        // 1. Create page
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Servers/Create'));

        // 2. Edit page
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.edit', $server->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Servers/Edit')
                ->where('server.id', $server->id)
            );

        // 3. Show page
        $this->actingAs($this->adminUser)
            ->get(route('admin.servers.show', $server->id))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Servers/Show')
                ->where('server.id', $server->id)
            );
    }
}
