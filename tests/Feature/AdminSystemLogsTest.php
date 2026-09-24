<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminSystemLogsTest extends TestCase
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

    public function test_admin_can_view_system_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.system'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/System/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_logs_by_source(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.system', ['source' => 'kernel', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/System/Index')
            ->where('filters.source', 'kernel')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_logs_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.system', ['search' => 'kernel', 'severity' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/System/Index')
            ->where('filters.search', 'kernel')
        );
    }

    public function test_admin_can_download_raw_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.system.download', ['source' => 'syslog']));

        $response->assertOk();
    }

    public function test_admin_can_flush_application_log_buffer(): void
    {
        $logPath = storage_path('logs/laravel.log');
        File::ensureDirectoryExists(storage_path('logs'));
        File::put($logPath, "Test log content to be cleared.");

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.system.flush'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEmpty(File::get($logPath));
    }
}
