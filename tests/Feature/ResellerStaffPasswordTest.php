<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantReseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResellerStaffPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_reset_own_staff_password_and_revoke_existing_sessions(): void
    {
        [$admin, $staff, $other, $owner] = $this->accounts();
        $this->actingAs($admin)->withSession(['locale' => 'en']);
        $this->get(route('reseller.staff.index'))->assertOk()->assertSee('Reset Password')
            ->assertSee($staff->email)->assertDontSee($other->email);

        $route = fn (User $user) => route('reseller.staff.reset-password', $user->id);
        $input = ['current_password' => 'ActorPassword', 'password' => 'NewStaffPassword', 'password_confirmation' => 'NewStaffPassword'];
        $this->postJson($route($other), $input)->assertNotFound();
        $this->postJson($route($owner), $input)->assertForbidden();
        $this->postJson($route($admin), $input)->assertForbidden();
        $this->postJson($route($staff), ['current_password' => 'WrongPassword'] + $input)
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertTrue(Hash::check('OldStaffPassword', $staff->refresh()->password));

        $oldToken = $staff->getRememberToken();
        config(['session.driver' => 'database']);
        DB::table('sessions')->insert([
            'id' => 'old-staff-session', 'user_id' => $staff->id, 'payload' => '', 'last_activity' => time(),
        ]);

        $this->postJson($route($staff), $input)->assertOk()->assertJsonPath('message', 'Staff password reset successfully.');
        $this->assertTrue(Hash::check('NewStaffPassword', $staff->refresh()->password));
        $this->assertNotSame($oldToken, $staff->getRememberToken());
        $this->assertDatabaseMissing('sessions', ['id' => 'old-staff-session']);
        $this->assertDatabaseHas('tenant_activity_logs', ['actor_id' => $admin->id, 'event_type' => 'staff_password_reset']);
        $this->assertTrue(Hash::check('ActorPassword', $admin->refresh()->password));
    }

    private function accounts(): array
    {
        $tenant = Tenant::create(['name' => 'Staff ISP', 'company_name' => 'Staff ISP', 'slug' => 'staff-isp', 'status' => 'active']);
        $reseller = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Local Staff', 'mobile' => '01710000032']);
        $other = TenantReseller::create(['tenant_id' => $tenant->id, 'name' => 'Other Staff', 'mobile' => '01710000033']);
        $user = fn ($partner, $role, $password) => User::factory()->create([
            'tenant_id' => $tenant->id, 'reseller_id' => $partner->id, 'role' => $role, 'status' => 'active', 'password' => Hash::make($password),
        ]);

        return [
            $user($reseller, 'reseller_manager', 'ActorPassword'),
            $user($reseller, 'reseller_collector', 'OldStaffPassword'),
            $user($other, 'reseller_collector', 'OtherStaffPassword'),
            $user($reseller, 'reseller_owner', 'OwnerStaffPassword'),
        ];
    }
}
