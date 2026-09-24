<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStorageManagementTest extends TestCase
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

    public function test_admin_can_view_storage_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.files.storage'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Files/Storage')
            ->has('primaryDisk')
            ->has('inodes')
            ->has('categories')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_vacuum_logs_and_flush_cache(): void
    {
        $responseLogs = $this->actingAs($this->adminUser)
            ->post(route('admin.files.storage.vacuum-logs'));

        $responseLogs->assertRedirect();
        $responseLogs->assertSessionHas('success');

        $responseCache = $this->actingAs($this->adminUser)
            ->post(route('admin.files.storage.flush-cache'));

        $responseCache->assertRedirect();
        $responseCache->assertSessionHas('success');
    }
}
