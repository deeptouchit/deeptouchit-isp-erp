<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDatabaseLogsTest extends TestCase
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

    public function test_admin_can_view_database_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.database'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Database/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_database_logs_by_source(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.database', ['source' => 'postgres', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Database/Index')
            ->where('filters.source', 'postgres')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_database_logs_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.database', ['search' => 'innodb', 'severity' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Database/Index')
            ->where('filters.search', 'innodb')
        );
    }

    public function test_admin_can_download_raw_database_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.database.download', ['source' => 'mysql_error']));

        $response->assertOk();
    }

    public function test_admin_can_flush_database_log_buffer(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.database.flush'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
