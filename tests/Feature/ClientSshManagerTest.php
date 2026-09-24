<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ClientSshManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
            'username' => 'sshclient_' . rand(1000, 9999),
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud-' . rand(100, 999),
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'max_domains' => 5,
            'max_databases' => 5,
            'max_email_accounts' => 10,
            'max_ftp_accounts' => 5,
            'allow_ssh_access' => true,
            'price_monthly' => 200,
            'price_yearly' => 2000,
            'is_active' => true,
        ]);

        $server = Server::create([
            'name' => 'Node 1',
            'hostname' => 'node1.test.com',
            'ip_address' => '127.0.0.1',
            'status' => 'online',
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'sshdomain.com',
            'username' => $this->clientUser->username,
            'document_root' => "/var/www/vhosts/{$this->clientUser->username}/sshdomain.com/public_html",
            'status' => 'active',
            'period' => 'monthly',
            'price' => 200,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $vhostDir = "/var/www/vhosts/{$this->subscription->username}/.ssh";
        if (!File::exists($vhostDir)) {
            File::makeDirectory($vhostDir, 0700, true, true);
        }
    }

    protected function tearDown(): void
    {
        $vhostDir = "/var/www/vhosts/{$this->subscription->username}";
        if (File::exists($vhostDir)) {
            File::deleteDirectory($vhostDir);
        }
        parent::tearDown();
    }

    public function test_client_can_view_ssh_hub(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get(route('advanced.ssh', ['subscription_id' => $this->subscription->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Advanced/SSH')
            ->has('username')
            ->has('domain')
            ->has('subscriptions')
            ->has('keys')
            ->has('serverInfo')
            ->has('sshEnabled')
        );
    }

    public function test_client_can_add_public_key(): void
    {
        $pubKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOmX1234567890abcdefghijklmnopqrstuvwxyz test@device';

        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.add-key'), [
                'subscription_id' => $this->subscription->id,
                'label' => 'MacBook Pro Key',
                'public_key' => $pubKey,
            ]);

        $response->assertSessionHas('success');

        $authKeysFile = "/var/www/vhosts/{$this->subscription->username}/.ssh/authorized_keys";
        $this->assertTrue(File::exists($authKeysFile));
        $this->assertStringContainsString('AAAAC3NzaC1lZDI1NTE5AAAAIOmX1234567890', File::get($authKeysFile));
    }

    public function test_client_can_generate_key_pair(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.generate-key'), [
                'subscription_id' => $this->subscription->id,
                'label' => 'Deployer_Ed25519',
                'key_type' => 'ed25519',
            ]);

        $response->assertSessionHas('generated_private_key');
        $response->assertSessionHas('success');

        $authKeysFile = "/var/www/vhosts/{$this->subscription->username}/.ssh/authorized_keys";
        $this->assertTrue(File::exists($authKeysFile));
        $this->assertStringContainsString('Deployer_Ed25519', File::get($authKeysFile));
    }

    public function test_client_can_toggle_ssh_access(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.toggle-access'), [
                'subscription_id' => $this->subscription->id,
            ]);

        $response->assertSessionHas('success');
        $disabledFile = "/var/www/vhosts/{$this->subscription->username}/.ssh/.ssh_disabled";
        $this->assertTrue(File::exists($disabledFile));

        // Toggle again to enable
        $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.toggle-access'), [
                'subscription_id' => $this->subscription->id,
            ]);
        $this->assertFalse(File::exists($disabledFile));
    }

    public function test_client_can_run_terminal_command(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.run-command'), [
                'subscription_id' => $this->subscription->id,
                'command' => 'echo "Hello SSH World"',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'exit_code' => 0,
        ]);
        $this->assertStringContainsString('Hello SSH World', $response->json('output'));
    }

    public function test_dangerous_commands_are_restricted(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.run-command'), [
                'subscription_id' => $this->subscription->id,
                'command' => 'sudo rm -rf /',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    public function test_client_can_change_ssh_password(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->post(route('advanced.ssh.change-password'), [
                'subscription_id' => $this->subscription->id,
                'password' => 'NewSecureSshPass123!',
            ]);

        $response->assertSessionHas('success');
    }
}
