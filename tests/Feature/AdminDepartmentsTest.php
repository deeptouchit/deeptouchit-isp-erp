<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDepartmentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected SupportDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->department = SupportDepartment::create([
            'name' => 'Custom Infrastructure Support',
            'slug' => 'custom-infra',
            'email' => 'infra@deeptouchhost.local',
            'description' => 'Dedicated cluster operations team.',
            'assigned_staff_ids' => [$this->adminUser->id],
            'is_client_selectable' => true,
            'is_active' => true,
            'sla_response_hours' => 2,
            'sort_order' => 5,
        ]);
    }

    public function test_admin_can_view_departments_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.support.departments'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Support/Departments/Index')
            ->has('departments')
            ->has('stats')
            ->has('staff')
        );
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.departments.store'), [
                'name' => 'VIP Concierge Desk',
                'slug' => 'vip-concierge',
                'email' => 'vip@deeptouchhost.local',
                'description' => 'Priority support for enterprise cluster customers.',
                'assigned_staff_ids' => [$this->adminUser->id],
                'is_client_selectable' => true,
                'is_active' => true,
                'sla_response_hours' => 1,
                'sort_order' => 6,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_departments', [
            'slug' => 'vip-concierge',
            'name' => 'VIP Concierge Desk',
            'sla_response_hours' => 1,
        ]);
    }

    public function test_admin_can_update_department(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.support.departments.update', $this->department->id), [
                'name' => 'Updated Custom Infrastructure',
                'slug' => 'custom-infra',
                'email' => 'infra-updated@deeptouchhost.local',
                'description' => 'Updated description.',
                'assigned_staff_ids' => [$this->adminUser->id],
                'is_client_selectable' => false,
                'is_active' => true,
                'sla_response_hours' => 3,
                'sort_order' => 5,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->department->refresh();
        $this->assertEquals('Updated Custom Infrastructure', $this->department->name);
        $this->assertEquals('infra-updated@deeptouchhost.local', $this->department->email);
        $this->assertEquals(3, $this->department->sla_response_hours);
        $this->assertFalse((bool)$this->department->is_client_selectable);
    }

    public function test_admin_can_toggle_department_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.departments.toggle-status', $this->department->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->department->fresh()->is_active);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.departments.toggle-status', $this->department->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->department->fresh()->is_active);
    }

    public function test_admin_can_delete_department(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.support.departments.destroy', $this->department->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('support_departments', ['id' => $this->department->id]);
    }
}
