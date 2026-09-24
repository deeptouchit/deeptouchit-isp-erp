<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminQueueTest extends TestCase
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

    public function test_admin_can_view_queues_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.queues'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/Queues')
            ->has('stats')
            ->has('pools')
            ->has('workers')
            ->has('pending_jobs')
            ->has('failed_jobs')
            ->has('batches')
        );
    }

    public function test_admin_can_fetch_queues_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.queues'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_workers',
                'pending_jobs',
                'failed_jobs',
                'pools_count',
                'driver',
            ],
            'pools',
            'workers',
            'pending_jobs',
            'failed_jobs',
            'batches',
        ]);
    }

    public function test_admin_can_restart_queue_workers(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.restart'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_dispatch_test_job(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.dispatch-test'), [
                'queue' => 'default',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_admin_can_manage_failed_jobs(): void
    {
        // Insert a dummy failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-failed-job-uuid-1234',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\SendEmailNotificationJob']),
            'exception' => 'Connection timeout to SMTP gateway on port 587',
            'failed_at' => now(),
        ]);

        $this->assertDatabaseHas('failed_jobs', [
            'uuid' => 'test-failed-job-uuid-1234',
        ]);

        // Retry
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.retry'), [
                'uuid' => 'test-failed-job-uuid-1234',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Retry all
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.retry-all'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Forget
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.forget'), [
                'uuid' => 'test-failed-job-uuid-1234',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Flush
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.queues.flush'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
