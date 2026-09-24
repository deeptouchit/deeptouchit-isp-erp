<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_accessible_when_maintenance_mode_is_off(): void
    {
        Setting::set('maintenance_mode', '0');

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_landing_page_shows_503_maintenance_when_maintenance_mode_is_on(): void
    {
        Setting::set('maintenance_mode', '1');
        Setting::set('maintenance_message', 'Emergency maintenance in progress.');

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee('Scheduled Platform Maintenance');
        $response->assertSee('Emergency maintenance in progress.');
    }

    public function test_owner_routes_remain_accessible_during_maintenance_mode(): void
    {
        Setting::set('maintenance_mode', '1');

        $response = $this->get('/owner/login');
        $response->assertStatus(200);
        $response->assertSee('Platform Owner Login');
    }

    public function test_authenticated_owner_can_access_dashboard_during_maintenance_mode(): void
    {
        Setting::set('maintenance_mode', '1');

        $owner = User::create([
            'name' => 'Platform Owner',
            'email' => 'owner@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $response = $this->get('/owner/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Overview Dashboard');
    }
}
