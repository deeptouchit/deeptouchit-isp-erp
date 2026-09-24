<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProcessesTest extends TestCase
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

    public function test_admin_can_view_processes_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.processes'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Processes/Index')
            ->has('processes')
            ->has('stats')
            ->has('users')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_processes_by_user(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.processes', ['user' => 'root']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Processes/Index')
            ->where('filters.user', 'root')
        );
    }

    public function test_admin_can_filter_processes_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.processes', ['status' => 'running']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Processes/Index')
            ->where('filters.status', 'running')
        );
    }

    public function test_admin_can_search_processes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.root-tools.processes', ['search' => 'node']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/RootTools/Processes/Index')
            ->where('filters.search', 'node')
        );
    }

    public function test_admin_cannot_terminate_protected_pid(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.root-tools.processes.kill'), [
                'pid' => 1,
                'signal' => 'SIGKILL',
            ]);

        $response->assertRedirect(route('admin.root-tools.processes'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_inspect_process_details(): void
    {
        $currentPid = getmypid();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.root-tools.processes.details', $currentPid));

        $response->assertOk();
        $response->assertJsonStructure([
            'pid',
            'cmdline',
            'status',
            'open_file_descriptors',
        ]);
    }
}
