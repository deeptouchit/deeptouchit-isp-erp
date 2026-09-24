<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ClientFileManagerTest extends TestCase
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
            'username' => 'testuser_' . rand(1000, 9999),
        ]);

        $plan = HostingPlan::create([
            'name' => 'Pro Cloud',
            'slug' => 'pro-cloud-' . rand(100, 999),
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'max_domains' => 5,
            'max_databases' => 5,
            'max_email_accounts' => 10,
            'price_monthly' => 200,
            'price_yearly' => 2000,
            'is_active' => true,
        ]);

        $server = \App\Models\Server::create([
            'name' => 'Node 1',
            'hostname' => 'node1.test.com',
            'ip_address' => '127.0.0.1',
            'status' => 'online',
        ]);

        $this->subscription = Subscription::create([
            'user_id' => $this->clientUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'testdomain.com',
            'username' => $this->clientUser->username,
            'document_root' => "/var/www/vhosts/{$this->clientUser->username}/testdomain.com/public_html",
            'status' => 'active',
            'period' => 'monthly',
            'price' => 200,
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        $publicHtml = "/var/www/vhosts/{$this->clientUser->username}/testdomain.com/public_html";
        if (!File::exists($publicHtml)) {
            File::makeDirectory($publicHtml, 0775, true, true);
        }
    }

    protected function tearDown(): void
    {
        $userDir = "/var/www/vhosts/{$this->clientUser->username}";
        if (File::exists($userDir)) {
            File::deleteDirectory($userDir);
        }
        parent::tearDown();
    }

    public function test_client_can_view_file_manager(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->get(route('file.browse'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/FileManager')
            ->has('subscriptions')
            ->has('currentSubscriptionId')
            ->has('initialFiles')
        );
    }

    public function test_client_can_upload_file(): void
    {
        $file = UploadedFile::fake()->create('testfile.txt', 50, 'text/plain');

        $response = $this->actingAs($this->clientUser)
            ->post(route('file.upload'), [
                'files' => [$file],
                'path' => "/testdomain.com/public_html",
                'subscription_id' => $this->subscription->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $uploadedPath = "/var/www/vhosts/{$this->subscription->username}/testdomain.com/public_html/testfile.txt";
        $this->assertTrue(File::exists($uploadedPath));
    }

    public function test_client_can_view_hidden_dotfiles_like_env_and_htaccess(): void
    {
        $publicHtml = "/var/www/vhosts/{$this->subscription->username}/testdomain.com/public_html";
        File::put($publicHtml . '/.env', 'APP_NAME=TestDotEnv');
        File::put($publicHtml . '/.htaccess', 'RewriteEngine On');
        File::makeDirectory($publicHtml . '/.well-known', 0775, true, true);

        $response = $this->actingAs($this->clientUser)
            ->get(route('file.list', [
                'subscription_id' => $this->subscription->id,
                'path' => '/testdomain.com/public_html',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'current_path',
            'total_items',
            'total_dirs',
            'total_files',
            'items',
        ]);

        $itemNames = collect($response->json('items'))->pluck('name')->toArray();
        $this->assertContains('.env', $itemNames);
        $this->assertContains('.htaccess', $itemNames);
        $this->assertContains('.well-known', $itemNames);

        $envItem = collect($response->json('items'))->firstWhere('name', '.env');
        $this->assertTrue($envItem['is_editable']);
    }
}

