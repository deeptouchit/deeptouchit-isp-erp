<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Announcement $announcement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->announcement = Announcement::create([
            'title' => 'Scheduled NVMe Pool Maintenance',
            'slug' => 'scheduled-nvme-pool-maintenance',
            'type' => 'maintenance',
            'severity' => 'warning',
            'target_audience' => 'all',
            'summary' => 'Routine cluster upgrade.',
            'content' => 'Routine cluster upgrade will occur on Sunday.',
            'is_published' => true,
            'is_pinned' => false,
            'show_banner' => true,
            'views_count' => 50,
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_admin_can_view_announcements_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.support.announcements'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Support/Announcements/Index')
            ->has('announcements')
            ->has('stats')
        );
    }

    public function test_admin_can_create_announcement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.announcements.store'), [
                'title' => 'PHP 8.4 Support Now Live',
                'slug' => 'php-84-support-now-live',
                'type' => 'promotional',
                'severity' => 'info',
                'target_audience' => 'all',
                'summary' => 'Enjoy 35% faster WordPress performance.',
                'content' => 'PHP 8.4 is now available in your control panel.',
                'is_published' => true,
                'is_pinned' => true,
                'show_banner' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('announcements', [
            'slug' => 'php-84-support-now-live',
            'title' => 'PHP 8.4 Support Now Live',
            'type' => 'promotional',
            'is_pinned' => true,
        ]);
    }

    public function test_admin_can_update_announcement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.support.announcements.update', $this->announcement->id), [
                'title' => 'Updated NVMe Pool Maintenance',
                'slug' => 'scheduled-nvme-pool-maintenance',
                'type' => 'maintenance',
                'severity' => 'critical',
                'target_audience' => 'clients_only',
                'summary' => 'Updated summary details.',
                'content' => 'Updated maintenance content details.',
                'is_published' => true,
                'is_pinned' => true,
                'show_banner' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->announcement->refresh();
        $this->assertEquals('Updated NVMe Pool Maintenance', $this->announcement->title);
        $this->assertEquals('critical', $this->announcement->severity);
        $this->assertEquals('clients_only', $this->announcement->target_audience);
    }

    public function test_admin_can_toggle_announcement_publish_state(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.announcements.toggle-publish', $this->announcement->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->announcement->fresh()->is_published);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.announcements.toggle-publish', $this->announcement->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->announcement->fresh()->is_published);
    }

    public function test_admin_can_toggle_announcement_pin_state(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.announcements.toggle-pin', $this->announcement->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->announcement->fresh()->is_pinned);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.support.announcements.toggle-pin', $this->announcement->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->announcement->fresh()->is_pinned);
    }

    public function test_admin_can_delete_announcement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.support.announcements.destroy', $this->announcement->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('announcements', ['id' => $this->announcement->id]);
    }
}
