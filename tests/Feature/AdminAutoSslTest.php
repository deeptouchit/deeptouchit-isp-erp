<?php

namespace Tests\Feature;

use App\Models\SslCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAutoSslTest extends TestCase
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

    public function test_admin_can_view_auto_ssl_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.automation.auto-ssl'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Automation/AutoSSL')
            ->has('stats')
            ->has('certificates')
            ->has('hostedDomains')
            ->has('subscriptions')
        );
    }

    public function test_admin_can_fetch_auto_ssl_live_api_telemetry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.automation.api.auto-ssl'));

        $response->assertOk();
        $response->assertJsonStructure([
            'stats' => [
                'total_certs',
                'active_certs',
                'autossl_count',
                'expiring_soon',
            ],
            'certificates',
        ]);
    }

    public function test_admin_can_issue_and_toggle_autossl(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-ssl.issue'), [
                'domain' => 'test-autossl-domain.com',
                'san_domains' => ['www.test-autossl-domain.com'],
                'force_https' => true,
                'hsts_enabled' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ssl_certificates', [
            'domain' => 'test-autossl-domain.com',
            'auto_renew' => true,
        ]);

        $cert = SslCertificate::where('domain', 'test-autossl-domain.com')->first();

        // Toggle auto renew
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-ssl.toggle-renew', ['id' => $cert->id]));

        $response->assertRedirect();
        $this->assertFalse((bool)$cert->fresh()->auto_renew);

        // Toggle force HTTPS
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-ssl.toggle-https', ['id' => $cert->id]));

        $response->assertRedirect();
        $this->assertFalse((bool)$cert->fresh()->force_https);

        // Renew now
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.automation.auto-ssl.renew-now', ['id' => $cert->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_run_autossl_sweep(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.automation.auto-ssl.sweep'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'renewed_count',
            'failed_count',
            'duration_ms',
        ]);
    }
}
