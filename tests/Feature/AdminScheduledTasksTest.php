<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduledTasksTest extends TestCase
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

    public function test_admin_can_view_scheduled_tasks_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.scheduled-tasks'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/ScheduledTasks')
            ->has('stats')
            ->has('tasks')
            ->has('categories')
        );
    }

    public function test_admin_can_fetch_scheduled_tasks_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.scheduled-tasks'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_tasks',
                'scheduler_status',
            ],
            'tasks',
            'categories',
        ]);
    }

    public function test_admin_can_run_scheduled_task_now(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.scheduled-tasks.run-now'), [
                'command' => 'inspire',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'status',
            'duration_ms',
        ]);
    }

    public function test_admin_can_dispatch_schedule_run(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.scheduled-tasks.run-schedule'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'duration_ms',
        ]);
    }
}
