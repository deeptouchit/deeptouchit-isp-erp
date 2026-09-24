<?php

namespace Tests\Feature;

use App\Models\SupportAgentProfile;
use App\Models\SupportDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAgentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        SupportAgentProfile::create([
            'user_id' => $this->staffUser->id,
            'job_title' => 'Tier-2 Linux Systems Engineer',
            'department_slugs' => ['technical', 'abuse'],
            'signature' => "--\nSupport Engineer\nDeepTouchHost Ops",
            'max_active_tickets' => 20,
            'is_auto_assignable' => true,
            'is_online' => true,
            'rating' => 5.00,
        ]);
    }

    public function test_admin_can_view_agents_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.support.agents'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Support/Agents/Index')
            ->has('agents')
            ->has('stats')
            ->has('departments')
            ->has('availableUsers')
        );
    }

    public function test_admin_can_create_or_update_agent_profile(): void
    {
        $newUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.agents.store'), [
                'user_id' => $newUser->id,
                'job_title' => 'Billing & Settlement Specialist',
                'department_slugs' => ['billing', 'sales'],
                'signature' => "--\nBilling Team",
                'max_active_tickets' => 15,
                'is_auto_assignable' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_agent_profiles', [
            'user_id' => $newUser->id,
            'job_title' => 'Billing & Settlement Specialist',
            'max_active_tickets' => 15,
        ]);
    }

    public function test_admin_can_update_existing_agent_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.support.agents.update', $this->staffUser->id), [
                'job_title' => 'Lead Infrastructure Engineer',
                'department_slugs' => ['technical'],
                'signature' => "--\nUpdated Signature",
                'max_active_tickets' => 30,
                'is_auto_assignable' => false,
                'is_online' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $profile = $this->staffUser->agentProfile->fresh();
        $this->assertEquals('Lead Infrastructure Engineer', $profile->job_title);
        $this->assertEquals(30, $profile->max_active_tickets);
        $this->assertFalse((bool)$profile->is_auto_assignable);
    }

    public function test_admin_can_toggle_agent_auto_assign(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.agents.toggle-auto-assign', $this->staffUser->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->staffUser->agentProfile->fresh()->is_auto_assignable);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.agents.toggle-auto-assign', $this->staffUser->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->staffUser->agentProfile->fresh()->is_auto_assignable);
    }

    public function test_admin_can_delete_agent_profile(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.support.agents.destroy', $this->staffUser->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('support_agent_profiles', ['user_id' => $this->staffUser->id]);
    }
}
