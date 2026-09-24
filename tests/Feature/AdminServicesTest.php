<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServicesTest extends TestCase
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

    public function test_admin_can_view_services_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.services'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Services/Index')
            ->has('services')
            ->has('stats')
            ->has('categories')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_services_by_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.services', ['category' => 'Web Stack & Proxies']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Services/Index')
            ->where('filters.category', 'Web Stack & Proxies')
        );
    }

    public function test_admin_can_filter_services_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.services', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Services/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_services(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.services', ['search' => 'nginx']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Services/Index')
            ->where('filters.search', 'nginx')
        );
    }

    public function test_admin_can_fetch_service_journal_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.root-tools.services.logs', 'nginx.service'));

        $response->assertOk();
        $response->assertJsonStructure([
            'unit',
            'name',
            'logs',
        ]);
        $this->assertEquals('nginx.service', $response->json('unit'));
    }

    public function test_admin_can_dispatch_service_action(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.services.action'), [
                'unit' => 'nginx.service',
                'action' => 'reload',
            ]);

        $response->assertRedirect(route('admin.root-tools.services'));
        $response->assertSessionHas('success');
    }
}
