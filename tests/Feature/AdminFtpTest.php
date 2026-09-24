<?php

namespace Tests\Feature;

use App\Models\FtpAccount;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFtpTest extends TestCase
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
            'name' => 'Starter Cloud',
            'slug' => 'starter-cloud',
            'description' => 'Standard starter package',
            'price_monthly' => 9.99,
            'price_yearly' => 99.99,
            'disk_space' => 5000,
            'bandwidth' => 50000,
            'max_domains' => 1,
            'max_databases' => 2,
            'max_ftp_accounts' => 5,
            'max_email_accounts' => 5,
            'is_active' => true,
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'demo-site.com',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 9.99,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_admin_can_view_ftp_accounts(): void
    {
        $ftpAccount = FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'ftp_tester',
            'password' => bcrypt('SecretPass123!'),
            'path' => '/var/www/vhosts/demosite/public_html',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.ftp'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Ftp/Index')
            ->has('ftpAccounts.data')
            ->has('stats')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_create_ftp_account(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.ftp.store'), [
                'subscription_id' => $this->subscription->id,
                'username' => 'ftp_admin_created',
                'password' => 'SecureKey12345!',
                'path' => 'public_html/uploads',
                'permissions' => 'readwrite',
            ]);

        $response->assertRedirect(route('admin.files.ftp'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ftp_accounts', [
            'username' => 'ftp_admin_created',
            'subscription_id' => $this->subscription->id,
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_toggle_ftp_status(): void
    {
        $ftpAccount = FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'ftp_to_suspend',
            'password' => bcrypt('SecretPass123!'),
            'path' => '/var/www/vhosts/demosite/public_html',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.ftp.toggle', $ftpAccount->id));

        $response->assertRedirect();
        $this->assertEquals('suspended', $ftpAccount->fresh()->status);
    }

    public function test_admin_can_change_ftp_password(): void
    {
        $ftpAccount = FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'ftp_pass_change',
            'password' => bcrypt('OldPass123!'),
            'path' => '/var/www/vhosts/demosite/public_html',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.ftp.password', $ftpAccount->id), [
                'password' => 'BrandNewStrongPass123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_delete_ftp_account(): void
    {
        $ftpAccount = FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'ftp_to_delete',
            'password' => bcrypt('SecretPass123!'),
            'path' => '/var/www/vhosts/demosite/public_html',
            'permissions' => 'readwrite',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.files.ftp.destroy', $ftpAccount->id));

        $response->assertRedirect(route('admin.files.ftp'));
        $this->assertDatabaseMissing('ftp_accounts', ['id' => $ftpAccount->id]);
    }

    public function test_admin_can_update_ftp_server_port_and_config(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.ftp.config'), [
                'ftp_port' => 2121,
                'pasv_min_port' => 30000,
                'pasv_max_port' => 31000,
                'update_firewall' => true,
                'restart_service' => false,
            ]);

        $response->assertRedirect(route('admin.files.ftp'));
        $response->assertSessionHas('success');
    }
}
