<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostingActivityTest extends TestCase
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

    public function test_admin_can_view_hosting_activity_directory(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'domain_vhost_created',
            'description' => "Deployed virtual host for domain 'sample.org'",
            'ip_address' => '127.0.0.1',
            'new_values' => ['domain' => 'sample.org'],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.hosting.activity'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Hosting/Activity/Index')
            ->has('activities.data')
            ->has('stats')
        );
    }

    public function test_admin_can_filter_hosting_activity_by_category(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'domain_ssl_issued',
            'description' => "Issued SSL for domain 'securesite.net'",
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.hosting.activity', ['action_type' => 'security']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('activities.data', 1)
        );
    }

    public function test_admin_can_export_hosting_activity_to_csv(): void
    {
        ActivityLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'hosting_account_created',
            'description' => "Provisioned hosting account for 'clientportal.com'",
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.hosting.activity.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
