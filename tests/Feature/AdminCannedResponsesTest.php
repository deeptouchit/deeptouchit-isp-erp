<?php

namespace Tests\Feature;

use App\Models\CannedResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCannedResponsesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected CannedResponse $cannedResponse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->cannedResponse = CannedResponse::create([
            'title' => 'DNS Propagation Notice',
            'category' => 'technical',
            'shortcut_code' => '!dns',
            'content' => 'Please allow 4 to 24 hours for DNS propagation.',
            'usage_count' => 10,
            'is_shared' => true,
            'created_by' => $this->adminUser->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_view_canned_responses_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.support.canned-responses'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Support/CannedResponses/Index')
            ->has('templates')
            ->has('stats')
        );
    }

    public function test_admin_can_create_canned_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.canned-responses.store'), [
                'title' => 'Custom Dedicated IP Allocation',
                'category' => 'technical',
                'shortcut_code' => '!ip',
                'content' => 'Your dedicated IP address has been provisioned.',
                'is_shared' => true,
                'sort_order' => 2,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('canned_responses', [
            'title' => 'Custom Dedicated IP Allocation',
            'shortcut_code' => '!ip',
            'category' => 'technical',
        ]);
    }

    public function test_admin_can_update_canned_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.support.canned-responses.update', $this->cannedResponse->id), [
                'title' => 'Updated DNS Propagation Notice',
                'category' => 'technical',
                'shortcut_code' => '!dns2',
                'content' => 'Updated propagation instructions.',
                'is_shared' => true,
                'sort_order' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->cannedResponse->refresh();
        $this->assertEquals('Updated DNS Propagation Notice', $this->cannedResponse->title);
        $this->assertEquals('!dns2', $this->cannedResponse->shortcut_code);
    }

    public function test_admin_can_increment_canned_response_usage(): void
    {
        $initialUsage = $this->cannedResponse->usage_count;

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.support.canned-responses.use', $this->cannedResponse->id));

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
            'usage_count' => $initialUsage + 1,
        ]);

        $this->assertEquals($initialUsage + 1, $this->cannedResponse->fresh()->usage_count);
    }

    public function test_admin_can_delete_canned_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.support.canned-responses.destroy', $this->cannedResponse->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('canned_responses', ['id' => $this->cannedResponse->id]);
    }
}
