<?php

namespace Tests\Feature;

use App\Models\CronJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCronTest extends TestCase
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

    public function test_admin_can_view_cron_automation_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.cron'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/Cron')
            ->has('stats')
            ->has('jobs')
            ->has('system_crons')
        );
    }

    public function test_admin_can_fetch_cron_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.cron'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_jobs',
                'enabled_jobs',
                'daemon_status',
            ],
            'jobs',
            'system_crons',
        ]);
    }

    public function test_admin_can_create_and_update_cron_job(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.cron.store'), [
                'title' => 'Test Laravel Schedule',
                'command' => 'php artisan schedule:run',
                'cron_expression' => '* * * * *',
                'description' => 'Dispatches scheduled tasks',
                'output_handling' => 'log_file',
                'run_as_user' => 'www-data',
                'is_enabled' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cron_jobs', [
            'title' => 'Test Laravel Schedule',
            'command' => 'php artisan schedule:run',
            'cron_expression' => '* * * * *',
        ]);

        $job = CronJob::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.automation.cron.update', ['id' => $job->id]), [
                'title' => 'Updated Schedule',
                'command' => 'php artisan schedule:run',
                'cron_expression' => '*/5 * * * *',
                'output_handling' => 'log_file',
                'run_as_user' => 'www-data',
                'is_enabled' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cron_jobs', [
            'id' => $job->id,
            'title' => 'Updated Schedule',
            'cron_expression' => '*/5 * * * *',
        ]);
    }

    public function test_admin_can_toggle_and_run_cron_job_now(): void
    {
        $job = CronJob::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Echo Test',
            'command' => 'echo "Hello DeepTouchHost Cron"',
            'cron_expression' => '0 0 * * *',
            'output_handling' => 'log_file',
            'run_as_user' => 'www-data',
            'is_enabled' => true,
        ]);

        // Toggle
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.cron.toggle', ['id' => $job->id]));

        $response->assertRedirect();
        $this->assertFalse((bool)$job->fresh()->is_enabled);

        // Run now
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.cron.run-now', ['id' => $job->id]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('cron_job_logs', [
            'cron_job_id' => $job->id,
            'status' => 'success',
        ]);

        // Fetch logs
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.cron.logs', ['id' => $job->id]));

        $response->assertOk();
        $response->assertJsonStructure([
            'job_id',
            'logs',
        ]);
    }

    public function test_admin_can_delete_cron_job(): void
    {
        $job = CronJob::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Temporary Task',
            'command' => 'ls -la',
            'cron_expression' => '0 * * * *',
            'output_handling' => 'log_file',
            'run_as_user' => 'www-data',
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.automation.cron.destroy', ['id' => $job->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('cron_jobs', ['id' => $job->id]);
    }
}
