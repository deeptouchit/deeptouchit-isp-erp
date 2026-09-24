<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Website;
use App\Services\PHP\PhpPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpPackageManagementTest extends TestCase
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

    public function test_admin_can_view_install_remove_catalog(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.install-remove'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/InstallRemove')
            ->has('catalog')
            ->has('stats')
        );
    }

    public function test_admin_can_install_new_php_version(): void
    {
        $this->mock(PhpPackageService::class, function ($mock) {
            $mock->shouldReceive('installVersion')
                ->once()
                ->with('8.4', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'PHP 8.4 installed.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.install-version'), [
                'version' => '8.4',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_uninstall_unused_php_version(): void
    {
        $this->mock(PhpPackageService::class, function ($mock) {
            $mock->shouldReceive('uninstallVersion')
                ->once()
                ->with('8.1', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'PHP 8.1 uninstalled.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.uninstall-version'), [
                'version' => '8.1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_toggle_php_extension(): void
    {
        $this->mock(\App\Services\PHP\PhpExtensionService::class, function ($mock) {
            $mock->shouldReceive('toggleExtension')
                ->once()
                ->with('8.5', 'redis', 'install', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Extension installed.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.toggle-extension'), [
                'version' => '8.5',
                'extension' => 'redis',
                'action' => 'install',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
