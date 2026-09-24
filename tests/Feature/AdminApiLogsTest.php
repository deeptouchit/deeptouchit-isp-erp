<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiLogsTest extends TestCase
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

        ApiLog::create([
            'method' => 'POST',
            'endpoint' => '/api/v1/test/provision',
            'status_code' => 200,
            'ip_address' => '103.59.177.138',
            'user_agent' => 'PHPUnit/10.0',
            'duration_ms' => 45,
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_payload' => ['plan' => 'Pro'],
            'response_body' => '{"success":true}',
        ]);
    }

    public function test_admin_can_view_api_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Logs/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_api_logs_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs', ['status' => '2xx']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Logs/Index')
            ->where('filters.status', '2xx')
        );
    }

    public function test_admin_can_filter_api_logs_by_method(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs', ['method' => 'POST']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Logs/Index')
            ->where('filters.method', 'POST')
        );
    }

    public function test_admin_can_search_api_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs', ['search' => '/api/v1/test/provision']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Logs/Index')
            ->where('filters.search', '/api/v1/test/provision')
        );
    }

    public function test_admin_can_show_api_log_details(): void
    {
        $log = ApiLog::first();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs.show', $log->id));

        $response->assertOk();
        $response->assertJson([
            'id' => $log->id,
            'endpoint' => $log->endpoint,
        ]);
    }

    public function test_admin_can_export_api_logs_to_csv(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_clear_api_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.logs.clear'));

        $response->assertRedirect(route('admin.api.logs'));
        $response->assertSessionHas('success');

        $this->assertEquals(0, ApiLog::count());
    }
}
