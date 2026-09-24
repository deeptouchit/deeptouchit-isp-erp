<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PHP\PhpFpmPoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpPoolManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_pools_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.pools'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/Pools')
            ->has('pools')
            ->has('stats')
            ->has('installedVersions')
        );
    }

    public function test_admin_can_update_fpm_pool_tuning(): void
    {
        $this->mock(PhpFpmPoolService::class, function ($mock) {
            $mock->shouldReceive('updatePool')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Pool tuned.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.pools.update'), [
                'version' => '8.5',
                'pool_name' => 'www',
                'pm' => 'dynamic',
                'pm_max_children' => 60,
                'pm_start_servers' => 6,
                'pm_min_spare_servers' => 6,
                'pm_max_spare_servers' => 30,
                'pm_max_requests' => 600,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_create_isolated_fpm_pool(): void
    {
        $this->mock(PhpFpmPoolService::class, function ($mock) {
            $mock->shouldReceive('createPool')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Pool created.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.pools.create'), [
                'version' => '8.5',
                'pool_name' => 'client_store',
                'pm' => 'dynamic',
                'pm_max_children' => 20,
                'pm_start_servers' => 4,
                'pm_min_spare_servers' => 2,
                'pm_max_spare_servers' => 10,
                'pm_max_requests' => 500,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_restart_fpm_daemon(): void
    {
        $this->mock(PhpFpmPoolService::class, function ($mock) {
            $mock->shouldReceive('restartPool')
                ->once()
                ->with('8.5', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'FPM restarted.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.pools.restart'), [
                'version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_delete_custom_fpm_pool(): void
    {
        $this->mock(PhpFpmPoolService::class, function ($mock) {
            $mock->shouldReceive('deletePool')
                ->once()
                ->with('8.5', 'custom_pool', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Pool deleted.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.php.pools.delete', [
                'version' => '8.5',
                'poolName' => 'custom_pool'
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
