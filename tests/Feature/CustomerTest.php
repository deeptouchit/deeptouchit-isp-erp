<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_view_active_customers(): void
    {
        $activeClient = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $suspendedClient = User::factory()->create([
            'role' => 'client',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.customers.active'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Active')
            ->has('customers.data')
            ->has('stats')
        );
    }

    public function test_admin_can_view_all_customers(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.customers.all'));

        $response->assertStatus(200);
    }

    public function test_admin_can_view_suspended_customers(): void
    {
        $suspendedClient = User::factory()->create([
            'role' => 'client',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.customers.suspended'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Suspended')
            ->has('customers.data')
            ->has('stats')
        );
    }

    public function test_admin_can_view_customer_activity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.customers.activity'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Activity')
            ->has('activityLogs.data')
            ->has('stats')
            ->has('distinctActions')
            ->has('usersList')
            ->has('recentLogins')
        );
    }
}
