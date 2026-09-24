<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSessionsTest extends TestCase
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

        DB::table('sessions')->insert([
            [
                'id' => 'test_session_id_1',
                'user_id' => $this->adminUser->id,
                'ip_address' => '103.59.177.138',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
                'payload' => serialize(['test' => 1]),
                'last_activity' => time(),
            ],
            [
                'id' => 'test_session_id_2',
                'user_id' => $this->adminUser->id,
                'ip_address' => '103.26.247.144',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
                'payload' => serialize(['test' => 2]),
                'last_activity' => time() - 300,
            ],
        ]);
    }

    public function test_admin_can_view_sessions_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.sessions'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Sessions/Index')
            ->has('sessions')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_sessions_by_scope(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.sessions', ['scope' => 'admins']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Sessions/Index')
            ->where('filters.scope', 'admins')
        );
    }

    public function test_admin_can_search_sessions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.administration.sessions', ['search' => '103.59.177.138']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Administration/Sessions/Index')
            ->where('filters.search', '103.59.177.138')
        );
    }

    public function test_admin_can_terminate_specific_session(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.administration.sessions.destroy', 'test_session_id_2'));

        $response->assertRedirect(route('admin.administration.sessions'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', [
            'id' => 'test_session_id_2',
        ]);
    }

    public function test_admin_can_terminate_all_other_sessions(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current' => 'active'])
            ->post(route('admin.administration.sessions.terminate-all-other'));

        $response->assertRedirect(route('admin.administration.sessions'));
        $response->assertSessionHas('success');
    }
}
