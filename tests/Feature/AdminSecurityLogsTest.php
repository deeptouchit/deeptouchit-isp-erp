<?php

namespace Tests\Feature;

use App\Models\IpBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityLogsTest extends TestCase
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

    public function test_admin_can_view_security_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.security'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Security/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_security_logs_by_source(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.security', ['source' => 'ufw', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Security/Index')
            ->where('filters.source', 'ufw')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_security_logs_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.security', ['search' => 'sshd', 'action_type' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Security/Index')
            ->where('filters.search', 'sshd')
        );
    }

    public function test_admin_can_download_raw_security_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.security.download', ['source' => 'fail2ban']));

        $response->assertOk();
    }

    public function test_admin_can_ban_and_unban_ip(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.security.ban-ip'), [
                'ip_address' => '198.51.100.77',
                'reason' => 'Brute-force attack test',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_blocks', [
            'ip_address' => '198.51.100.77',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.security.unban-ip'), [
                'ip_address' => '198.51.100.77',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('ip_blocks', [
            'ip_address' => '198.51.100.77',
        ]);
    }
}
