<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogsTest extends TestCase
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

        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'ip_banned',
            'description' => 'Banned IP 198.51.100.55 in firewall.',
            'ip_address' => '103.59.177.138',
            'user_agent' => 'Admin Panel',
            'old_values' => [],
            'new_values' => ['ip' => '198.51.100.55'],
        ]);

        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'maintenance_mode_toggled',
            'description' => 'Toggled server maintenance mode.',
            'ip_address' => '103.59.177.138',
            'user_agent' => 'Admin Panel',
            'old_values' => ['active' => false],
            'new_values' => ['active' => true],
        ]);
    }

    public function test_admin_can_view_audit_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.audit'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Audit/Index')
            ->has('activityLogs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_audit_logs_by_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.audit', ['category' => 'security']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Audit/Index')
            ->where('filters.category', 'security')
        );
    }

    public function test_admin_can_search_audit_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.audit', ['search' => 'maintenance']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Audit/Index')
            ->where('filters.search', 'maintenance')
        );
    }

    public function test_admin_can_export_audit_logs_csv(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.audit.export'));

        $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_admin_can_clear_audit_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.audit.clear'), ['days' => 0]);

        $response->assertRedirect(route('admin.logs.audit'));
        $response->assertSessionHas('success');
    }
}
