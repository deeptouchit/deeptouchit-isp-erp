<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebServerLogsTest extends TestCase
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

    public function test_admin_can_view_web_server_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.web-server'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/WebServer/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_logs_by_source(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.web-server', ['source' => 'nginx_error', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/WebServer/Index')
            ->where('filters.source', 'nginx_error')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_logs_by_status_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.web-server', ['status_code' => '2xx']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/WebServer/Index')
            ->where('filters.status_code', '2xx')
        );
    }

    public function test_admin_can_filter_logs_by_method(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.web-server', ['method' => 'GET']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/WebServer/Index')
            ->where('filters.method', 'GET')
        );
    }

    public function test_admin_can_download_raw_web_server_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.web-server.download', ['source' => 'nginx_access']));

        $response->assertOk();
    }
}
