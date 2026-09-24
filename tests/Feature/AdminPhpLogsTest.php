<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPhpLogsTest extends TestCase
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

    public function test_admin_can_view_php_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.php'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Php/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_php_logs_by_version(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.php', ['version' => '8.4', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Php/Index')
            ->where('filters.version', '8.4')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_php_logs_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.php', ['search' => 'fpm', 'severity' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Php/Index')
            ->where('filters.search', 'fpm')
        );
    }

    public function test_admin_can_download_raw_php_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.php.download', ['version' => '8.2']));

        $response->assertOk();
    }

    public function test_admin_can_restart_php_fpm_daemon(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.php.restart'), ['version' => '8.2']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
