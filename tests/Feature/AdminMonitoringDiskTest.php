<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonitoringDiskTest extends TestCase
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

    public function test_admin_can_view_monitoring_disk_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.monitoring.disk'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Monitoring/Disk')
            ->has('stats')
            ->has('partitions')
            ->has('inodes')
            ->has('iops')
            ->has('directories')
        );
    }

    public function test_admin_can_poll_api_disk_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.monitoring.api.disk'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stats',
            'partitions',
            'inodes',
            'iops',
            'directories',
        ]);
    }

    public function test_admin_can_vacuum_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.vacuum-logs'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_clean_temp(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.monitoring.clean-temp'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
