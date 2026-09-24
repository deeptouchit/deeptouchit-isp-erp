<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailLogManagementTest extends TestCase
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

    public function test_admin_can_view_email_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.email.logs'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Email/Logs')
            ->has('logs')
            ->has('stats')
        );
    }

    public function test_admin_can_clear_email_logs(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.email.logs.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
