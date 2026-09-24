<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Fail2banManagementTest extends TestCase
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

    public function test_admin_can_view_fail2ban_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.fail2ban'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/Fail2ban')
            ->has('overview')
            ->has('jails')
            ->has('bannedIps')
            ->has('stats')
        );
    }

    public function test_admin_can_ban_and_unban_ip_in_jail(): void
    {
        $responseBan = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.ban'), [
                'jail' => 'sshd',
                'ip' => '198.51.100.77',
            ]);

        $responseBan->assertRedirect();
        $responseBan->assertSessionHas('success');

        $responseUnban = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.unban'), [
                'jail' => 'sshd',
                'ip' => '198.51.100.77',
            ]);

        $responseUnban->assertRedirect();
        $responseUnban->assertSessionHas('success');
    }

    public function test_admin_can_elevate_fail2ban_ip_to_permanent_block(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.elevate'), [
                'jail' => 'sshd',
                'ip' => '198.51.100.88',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_blocks', [
            'ip_address' => '198.51.100.88',
        ]);
    }

    public function test_admin_can_whitelist_fail2ban_ip(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.whitelist'), [
                'jail' => 'sshd',
                'ip' => '198.51.100.66',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ip_allowlists', [
            'ip_address' => '198.51.100.66',
        ]);
    }

    public function test_admin_can_flush_bans_and_restart(): void
    {
        $responseFlush = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.unban-all'));

        $responseFlush->assertRedirect();
        $responseFlush->assertSessionHas('success');

        $responseRestart = $this->actingAs($this->adminUser)
            ->post(route('admin.security.fail2ban.restart'));

        $responseRestart->assertRedirect();
        $responseRestart->assertSessionHas('success');
    }
}
