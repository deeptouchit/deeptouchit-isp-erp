<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PHP\PhpExtensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpExtensionManagementTest extends TestCase
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

    public function test_admin_can_view_extensions_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.php.extensions'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/PHP/Extensions')
            ->has('catalog')
            ->has('categories')
            ->has('stats')
            ->has('installedVersions')
        );
    }

    public function test_admin_can_toggle_extension(): void
    {
        $this->mock(PhpExtensionService::class, function ($mock) {
            $mock->shouldReceive('toggleExtension')
                ->once()
                ->with('8.5', 'redis', 'enable', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Extension enabled.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.extensions.toggle'), [
                'version' => '8.5',
                'extension' => 'redis',
                'action' => 'enable',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_install_recommended_stack(): void
    {
        $this->mock(PhpExtensionService::class, function ($mock) {
            $mock->shouldReceive('installRecommendedStack')
                ->once()
                ->with('8.5', $this->adminUser->id)
                ->andReturn(['success' => true, 'message' => 'Recommended stack installed.']);
        });

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.php.extensions.install-recommended'), [
                'version' => '8.5',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
