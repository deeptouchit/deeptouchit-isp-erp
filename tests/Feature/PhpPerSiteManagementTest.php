<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\PHP\PhpCompatibilityService;
use App\Services\PHP\PhpManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpPerSiteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $server = \App\Models\Server::factory()->create();

        $plan = \App\Models\HostingPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'disk_space' => 10240,
            'bandwidth' => 102400,
            'price_monthly' => 10.00,
            'price_yearly' => 100.00,
            'is_active' => true,
        ]);

        $sub = Subscription::create([
            'user_id' => $this->adminUser->id,
            'plan_id' => $plan->id,
            'server_id' => $server->id,
            'domain' => 'example-test.com',
            'status' => 'active',
            'period' => 'monthly',
            'price' => 10.00,
            'php_version' => '8.2',
            'next_billing_date' => now()->addMonth(),
            'expires_at' => now()->addYear(),
        ]);

        $this->website = Website::create([
            'subscription_id' => $sub->id,
            'domain' => 'example-test.com',
            'document_root' => '/var/www/vhosts/example-test.com/public',
            'php_version' => '8.2',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_per_site_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.per-site'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/PerSite')
            ->has('websites')
            ->has('stats')
            ->has('installedVersions')
        );
    }

    public function test_admin_can_switch_website_php_version(): void
    {
        $this->mock(PhpManagerService::class, function ($mock) {
            $mock->shouldReceive('switchWebsitePhpVersion')
                ->once()
                ->andReturn(['success' => true, 'message' => 'PHP switched successfully.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.per-site.switch'), [
                'website_id' => $this->website->id,
                'target_version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_check_compatibility(): void
    {
        $this->mock(PhpCompatibilityService::class, function ($mock) {
            $mock->shouldReceive('checkCompatibility')
                ->once()
                ->andReturn([
                    'status' => 'compatible',
                    'app_type' => 'Laravel Framework',
                    'warnings' => [],
                ]);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.per-site.compatibility'), [
                'website_id' => $this->website->id,
                'target_version' => '8.5',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'compatible',
            'app_type' => 'Laravel Framework',
        ]);
    }

    public function test_admin_can_batch_switch_php_version(): void
    {
        $this->mock(PhpManagerService::class, function ($mock) {
            $mock->shouldReceive('switchWebsitePhpVersion')
                ->once()
                ->andReturn(['success' => true, 'message' => 'PHP switched.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.per-site.batch-switch'), [
                'website_ids' => [$this->website->id],
                'target_version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
