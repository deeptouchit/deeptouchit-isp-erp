<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCleanupManagementTest extends TestCase
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

    public function test_admin_can_view_cleanup_console(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.cleanup'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Cleanup')
            ->has('categories')
            ->has('stats')
        );
    }

    public function test_admin_can_clean_single_category(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.cleanup.clean', 'app_cache'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_perform_universal_cleanup(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.files.cleanup.all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
