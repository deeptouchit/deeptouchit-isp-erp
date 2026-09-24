<?php

namespace Tests\Feature;

use App\Models\FtpAccount;
use App\Models\HostingPlan;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FTPManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FTPControllerTest extends TestCase
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
            'username' => 'ftpclient_' . rand(1000, 9999),
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud-' . rand(100, 999),
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'max_domains' => 5,
            'max_databases' => 5,
            'max_email_accounts' => 10,
            'max_ftp_accounts' => 2,
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
            'domain' => 'myftpdomain.com',
            'username' => $this->clientUser->username,
            'document_root' => "/var/www/vhosts/{$this->clientUser->username}/myftpdomain.com/public_html",
            'status' => 'active',
            'period' => 'monthly',
            'price' => 200,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function test_client_can_view_ftp_accounts_page(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get(route('ftp-accounts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/FTP/Index')
            ->has('ftpAccounts')
            ->has('subscriptions')
            ->has('quota')
            ->has('serverInfo')
        );
    }

    public function test_client_can_create_ftp_account(): void
    {
        $mockFtpManager = $this->createMock(FTPManager::class);
        $mockFtpManager->expects($this->once())
            ->method('createFtpUser')
            ->willReturn(['success' => true]);
        $this->app->instance(FTPManager::class, $mockFtpManager);

        $response = $this->actingAs($this->clientUser)
            ->post(route('ftp-accounts.store'), [
                'subscription_id' => $this->subscription->id,
                'username' => 'dev_user',
                'password' => 'SecurePass123!',
                'path' => 'public_html',
                'permissions' => 'readwrite',
            ]);

        $response->assertRedirect(route('ftp-accounts.index'));
        $this->assertDatabaseHas('ftp_accounts', [
            'subscription_id' => $this->subscription->id,
            'username' => 'dev_user',
        ]);
    }

    public function test_ftp_quota_enforcement(): void
    {
        FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'user_one',
            'password' => 'secret123',
            'path' => '/var/www/vhosts/test',
        ]);
        FtpAccount::create([
            'subscription_id' => $this->subscription->id,
            'username' => 'user_two',
            'password' => 'secret123',
            'path' => '/var/www/vhosts/test',
        ]);

        $response = $this->actingAs($this->clientUser)
            ->post(route('ftp-accounts.store'), [
                'subscription_id' => $this->subscription->id,
                'username' => 'user_three',
                'password' => 'SecurePass123!',
                'path' => 'public_html',
            ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('ftp_accounts', [
            'username' => 'user_three',
        ]);
    }
}
