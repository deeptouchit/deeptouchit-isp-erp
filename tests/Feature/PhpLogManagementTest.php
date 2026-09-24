<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\PHP\PhpLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpLogManagementTest extends TestCase
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
            'action' => 'php_pool_updated',
            'description' => 'Tuned FPM pool www',
            'ip_address' => '127.0.0.1',
            'old_values' => ['max_children' => 50],
            'new_values' => ['max_children' => 100],
        ]);
    }

    public function test_admin_can_view_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.logs'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/Logs')
            ->has('fpmLogs')
            ->has('auditLogs')
            ->has('stats')
            ->has('installedVersions')
        );
    }

    public function test_admin_can_clear_fpm_logs(): void
    {
        $this->mock(PhpLogService::class, function ($mock) {
            $mock->shouldReceive('clearFpmLogs')
                ->once()
                ->with('8.5', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Log cleared.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.logs.clear'), [
                'version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
