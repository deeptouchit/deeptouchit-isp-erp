<?php

namespace Tests\Feature;

use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
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
            'name' => 'Technical Support Ops',
            'slug' => 'tech-support-ops',
            'email' => 'support-ops@deeptouchhost.local',
            'description' => 'Dedicated cluster operations team.',
        ]);

        User::factory()->create([
            'role' => 'admin',
            'is_staff' => true,
            'staff_id' => 'SH-STF-999',
            'department_id' => $this->department->id,
            'designation' => 'Cloud Support Specialist',
            'shift' => 'Morning (08:00 - 16:00)',
            'shift_status' => 'on_duty',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_staff_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.staff'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Staff/Index')
            ->has('staff')
            ->has('stats')
            ->has('departments')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_staff_by_department(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.staff', ['department_id' => $this->department->id]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Staff/Index')
            ->where('filters.department_id', (string) $this->department->id)
        );
    }

    public function test_admin_can_filter_staff_by_shift_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.staff', ['shift_status' => 'on_duty']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Staff/Index')
            ->where('filters.shift_status', 'on_duty')
        );
    }

    public function test_admin_can_filter_staff_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.staff', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Staff/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_staff(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.staff', ['search' => 'SH-STF-999']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Staff/Index')
            ->where('filters.search', 'SH-STF-999')
        );
    }

    public function test_admin_can_create_new_staff_member(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.staff.store'), [
                'first_name' => 'Mahbub',
                'last_name' => 'Alam',
                'email' => 'mahbub@deeptouchhost.com',
                'username' => 'mahbub_tech',
                'password' => 'StaffPass123!@#',
                'phone' => '+8801700000199',
                'staff_id' => 'SH-STF-888',
                'department_id' => $this->department->id,
                'designation' => 'Junior NOC Analyst',
                'shift' => 'Evening (16:00 - 00:00)',
                'shift_status' => 'on_duty',
                'two_factor_enforced' => true,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.administration.staff'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'mahbub@deeptouchhost.com',
            'staff_id' => 'SH-STF-888',
            'is_staff' => true,
        ]);
    }

    public function test_admin_can_update_staff_member(): void
    {
        $staffMember = User::where('staff_id', 'SH-STF-999')->first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.administration.staff.update', $staffMember->id), [
                'first_name' => 'UpdatedStaffFirst',
                'last_name' => 'UpdatedStaffLast',
                'email' => $staffMember->email,
                'username' => $staffMember->username,
                'staff_id' => 'SH-STF-999',
                'department_id' => $this->department->id,
                'designation' => 'Lead Infrastructure Architect',
                'shift' => 'Morning (08:00 - 16:00)',
                'shift_status' => 'on_duty',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.administration.staff'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $staffMember->id,
            'first_name' => 'UpdatedStaffFirst',
            'designation' => 'Lead Infrastructure Architect',
        ]);
    }

    public function test_admin_can_toggle_staff_shift_duty(): void
    {
        $staffMember = User::where('staff_id', 'SH-STF-999')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.staff.toggle-duty', $staffMember->id));

        $response->assertRedirect(route('admin.administration.staff'));
        $response->assertSessionHas('success');

        $this->assertEquals('off_duty', $staffMember->fresh()->shift_status);
    }

    public function test_admin_can_toggle_staff_status(): void
    {
        $staffMember = User::where('staff_id', 'SH-STF-999')->first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.administration.staff.toggle-status', $staffMember->id));

        $response->assertRedirect(route('admin.administration.staff'));
        $response->assertSessionHas('success');

        $this->assertEquals('suspended', $staffMember->fresh()->status);
    }

    public function test_admin_can_delete_staff_member(): void
    {
        $staffMember = User::where('staff_id', 'SH-STF-999')->first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.staff.destroy', $staffMember->id));

        $response->assertRedirect(route('admin.administration.staff'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('users', [
            'id' => $staffMember->id,
        ]);
    }
}
