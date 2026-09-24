<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackagesTest extends TestCase
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

    public function test_admin_can_view_packages_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.packages'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Packages/Index')
            ->has('packages')
            ->has('stats')
            ->has('categories')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_packages_by_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.packages', ['category' => 'Web Stack']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Packages/Index')
            ->where('filters.category', 'Web Stack')
        );
    }

    public function test_admin_can_filter_upgradable_packages(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.packages', ['category' => 'upgradable']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Packages/Index')
            ->where('filters.category', 'upgradable')
        );
    }

    public function test_admin_can_search_packages(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.packages', ['search' => 'curl']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Packages/Index')
            ->where('filters.search', 'curl')
        );
    }

    public function test_admin_can_inspect_package_details(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.root-tools.packages.details', 'curl'));

        $response->assertOk();
        $response->assertJsonStructure([
            'package',
            'version',
            'architecture',
            'installed_size',
            'description',
        ]);
        $this->assertEquals('curl', $response->json('package'));
    }

    public function test_admin_can_update_package_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.packages.update-index'));

        $response->assertRedirect(route('admin.root-tools.packages'));
        $response->assertSessionHas('success');
    }

    public function test_admin_can_upgrade_package(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.packages.upgrade'), [
                'package' => 'curl',
            ]);

        $response->assertRedirect(route('admin.root-tools.packages'));
        $response->assertSessionHas('success');
    }
}
