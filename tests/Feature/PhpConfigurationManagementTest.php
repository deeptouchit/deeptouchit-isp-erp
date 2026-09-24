<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PHP\PhpConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpConfigurationManagementTest extends TestCase
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

    public function test_admin_can_view_configuration_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.configuration'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/Configuration')
            ->has('directives')
            ->has('stats')
            ->has('installedVersions')
        );
    }

    public function test_admin_can_update_configuration_directives(): void
    {
        $this->mock(PhpConfigurationService::class, function ($mock) {
            $mock->shouldReceive('updateDirectives')
                ->once()
                ->andReturn(['success' => true, 'message' => 'Configuration updated.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.configuration.update'), [
                'version' => '8.5',
                'settings' => [
                    'memory_limit' => '1024M',
                    'upload_max_filesize' => '512M',
                    'post_max_size' => '512M',
                    'max_execution_time' => '600',
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_reset_configuration(): void
    {
        $this->mock(PhpConfigurationService::class, function ($mock) {
            $mock->shouldReceive('resetToDefaults')
                ->once()
                ->with('8.5', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Configuration reset.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.configuration.reset'), [
                'version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_update_raw_configuration(): void
    {
        $this->mock(PhpConfigurationService::class, function ($mock) {
            $mock->shouldReceive('updateRawIni')
                ->once()
                ->with('8.5', 'memory_limit = 512M', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Raw INI updated.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.configuration.raw'), [
                'version' => '8.5',
                'raw_content' => 'memory_limit = 512M',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
