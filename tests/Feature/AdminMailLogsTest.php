<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMailLogsTest extends TestCase
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

    public function test_admin_can_view_mail_logs_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.mail'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Mail/Index')
            ->has('logs')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_mail_logs_by_source(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.mail', ['source' => 'opendkim', 'lines' => 50]));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Mail/Index')
            ->where('filters.source', 'opendkim')
            ->where('filters.lines', 50)
        );
    }

    public function test_admin_can_filter_mail_logs_by_search_keyword(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.mail', ['search' => 'postfix', 'status' => 'all']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Logs/Mail/Index')
            ->where('filters.search', 'postfix')
        );
    }

    public function test_admin_can_download_raw_mail_log(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.logs.mail.download', ['source' => 'postfix_all']));

        $response->assertOk();
    }

    public function test_admin_can_flush_mail_queue(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.logs.mail.flush'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
