<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\SftpUser;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSftpTest extends TestCase
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
        ]);

        $this->clientUser = User::factory()->create([
            'role' => 'client',
        ]);

        $server = Server::factory()->create([
            'name' => 'Primary Node',
            'status' => 'online',
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud',
            'description' => 'Professional plan',
            'price_monthly' => 19.99,
            'price_yearly' => 199.99,
            'disk_space' => 10000,
            'bandwidth' => 100000,
            'max_domains' => 5,
            'max_databases' => 10,
            'max_ftp_accounts' => 10,
            'max_email_accounts' => 20,
            'is_active' => true,
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'sftp-demo.com',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 19.99,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_admin_can_view_sftp_users_directory(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.sftp'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Sftp/Index')
            ->has('sftpUsers.data')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_provision_sftp_user_with_password(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.sftp.store'), [
                'subscription_id' => $this->subscription->id,
                'username' => 'sftp_app_deployer',
                'auth_type' => 'password',
                'password' => 'SuperSecretPass123!',
                'path' => 'public_html',
                'shell' => '/usr/lib/openssh/sftp-server',
                'permissions' => 'readwrite',
            ]);

        $response->assertRedirect(route('admin.files.sftp'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sftp_users', [
            'username' => 'sftp_app_deployer',
            'subscription_id' => $this->subscription->id,
            'auth_type' => 'password',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_provision_sftp_user_with_ssh_key(): void
    {
        $pubKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIG... dev@machine';

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.sftp.store'), [
                'subscription_id' => $this->subscription->id,
                'username' => 'sftp_key_user',
                'auth_type' => 'key',
                'public_key' => $pubKey,
                'path' => 'public_html',
                'shell' => '/usr/lib/openssh/sftp-server',
                'permissions' => 'readwrite',
            ]);

        $response->assertRedirect(route('admin.files.sftp'));
        $this->assertDatabaseHas('sftp_users', [
            'username' => 'sftp_key_user',
            'auth_type' => 'key',
            'public_key' => $pubKey,
        ]);
    }

    public function test_admin_can_toggle_sftp_user_status(): void
    {
        $sftpUser = SftpUser::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'sftp_toggle_test',
            'auth_type' => 'password',
            'password' => bcrypt('Pass12345!'),
            'path' => '/var/www/vhosts/sftpdemo',
            'shell' => '/usr/lib/openssh/sftp-server',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.sftp.toggle', $sftpUser->id));

        $response->assertRedirect();
        $this->assertEquals('suspended', $sftpUser->fresh()->status);
    }

    public function test_admin_can_change_sftp_password(): void
    {
        $sftpUser = SftpUser::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'sftp_pwd_test',
            'auth_type' => 'password',
            'password' => bcrypt('OldPass123!'),
            'path' => '/var/www/vhosts/sftpdemo',
            'shell' => '/usr/lib/openssh/sftp-server',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.sftp.password', $sftpUser->id), [
                'password' => 'NewStrongPass987!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_update_sftp_ssh_key(): void
    {
        $sftpUser = SftpUser::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'sftp_update_key',
            'auth_type' => 'password',
            'password' => bcrypt('Pass123!'),
            'path' => '/var/www/vhosts/sftpdemo',
            'shell' => '/usr/lib/openssh/sftp-server',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $newKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC... user@workstation';

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.sftp.key', $sftpUser->id), [
                'public_key' => $newKey,
            ]);

        $response->assertRedirect();
        $this->assertEquals($newKey, $sftpUser->fresh()->public_key);
        $this->assertEquals('both', $sftpUser->fresh()->auth_type);
    }

    public function test_admin_can_delete_sftp_user(): void
    {
        $sftpUser = SftpUser::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'sftp_del_test',
            'auth_type' => 'password',
            'password' => bcrypt('Pass123!'),
            'path' => '/var/www/vhosts/sftpdemo',
            'shell' => '/usr/lib/openssh/sftp-server',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.files.sftp.destroy', $sftpUser->id));

        $response->assertRedirect(route('admin.files.sftp'));
        $this->assertDatabaseMissing('sftp_users', ['id' => $sftpUser->id]);
    }
}
