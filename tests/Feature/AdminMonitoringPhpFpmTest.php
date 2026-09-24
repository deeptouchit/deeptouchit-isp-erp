<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringPhpFpmTest extends TestCase
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

    public function test_admin_can_view_php_fpm_monitoring_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.php-fpm'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Monitoring/PhpFpm')
            ->has('stats')
            ->has('versions')
            ->has('workers')
            ->has('logs')
        );
    }

    public function test_admin_can_fetch_php_fpm_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.php-fpm'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_installed',
                'active_versions',
                'total_workers',
                'total_memory_formatted',
            ],
            'versions',
            'workers',
            'logs',
        ]);
    }

    public function test_admin_can_reload_php_fpm_pool(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.php-fpm.reload'), [
                'version' => '8.2',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_restart_php_fpm_pool(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.php-fpm.restart'), [
                'version' => '8.2',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
