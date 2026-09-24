<?php

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginHistoryTest extends TestCase
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

        LoginHistory::create([
            'user_id' => $this->adminUser->id,
            'email' => $this->adminUser->email,
            'role' => 'admin',
            'ip_address' => '103.59.177.138',
            'location' => 'Dhaka, Bangladesh',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128',
            'device' => 'Desktop',
            'browser' => 'Chrome',
            'os' => 'Windows 11',
            'status' => 'success',
            'login_at' => now(),
        ]);

        LoginHistory::create([
            'user_id' => null,
            'email' => 'hacker@example.com',
            'role' => 'client',
            'ip_address' => '198.51.100.22',
            'location' => 'Unknown',
            'user_agent' => 'Curl/7.68.0',
            'device' => 'Bot',
            'browser' => 'Curl',
            'os' => 'Linux',
            'status' => 'failed',
            'failure_reason' => 'Invalid password',
            'login_at' => now()->subMinutes(10),
        ]);
    }

    public function test_admin_can_view_login_history_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.login-history'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/LoginHistory/Index')
            ->has('loginHistories')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_login_history_by_role(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.login-history', ['role' => 'admin']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/LoginHistory/Index')
            ->where('filters.role', 'admin')
        );
    }

    public function test_admin_can_filter_login_history_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.login-history', ['status' => 'failed']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/LoginHistory/Index')
            ->where('filters.status', 'failed')
        );
    }

    public function test_admin_can_search_login_history(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.login-history', ['search' => 'Dhaka']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/LoginHistory/Index')
            ->where('filters.search', 'Dhaka')
        );
    }

    public function test_admin_can_export_login_history_csv(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.login-history.export'));

        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_admin_can_clear_login_history(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.login-history.clear'), ['days' => 0]);

        $response->assertRedirect(route('admin.logs.login-history'));
        $response->assertSessionHas('success');
        $this->assertEquals(0, LoginHistory::count());
    }
}
