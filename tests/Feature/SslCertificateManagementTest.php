<?php

namespace Tests\Feature;

use App\Models\SslCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SslCertificateManagementTest extends TestCase
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

    public function test_admin_can_view_ssl_certificates_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.security.ssl'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Security/SSL')
            ->has('certificates')
            ->has('stats')
        );
    }

    public function test_admin_can_issue_letsencrypt_certificate(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.ssl.issue-letsencrypt'), [
                'domain' => 'ssltest.com',
                'san_domains' => ['www.ssltest.com', 'mail.ssltest.com'],
                'force_https' => true,
                'hsts_enabled' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ssl_certificates', [
            'domain' => 'ssltest.com',
            'type' => 'letsencrypt',
            'force_https' => true,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_generate_self_signed_certificate(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.security.ssl.generate-self-signed'), [
                'domain' => 'staging.local',
                'valid_days' => 365,
                'force_https' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ssl_certificates', [
            'domain' => 'staging.local',
            'type' => 'self_signed',
        ]);
    }

    public function test_admin_can_toggle_force_https_and_renew(): void
    {
        $cert = SslCertificate::create([
            'domain' => 'toggle-test.com',
            'type' => 'letsencrypt',
            'certificate' => 'CERT',
            'private_key' => 'KEY',
            'valid_from' => now(),
            'valid_to' => now()->addDays(90),
            'force_https' => false,
            'auto_renew' => true,
            'status' => 'active',
        ]);

        $responseHttps = $this->actingAs($this->adminUser)
            ->post(route('admin.security.ssl.toggle-force-https', $cert->id));

        $responseHttps->assertRedirect();
        $this->assertTrue($cert->fresh()->force_https);

        $responseRenew = $this->actingAs($this->adminUser)
            ->post(route('admin.security.ssl.renew', $cert->id));

        $responseRenew->assertRedirect();
        $this->assertEquals('active', $cert->fresh()->status);
    }

    public function test_admin_can_download_and_delete_certificate(): void
    {
        $cert = SslCertificate::create([
            'domain' => 'download-test.com',
            'type' => 'letsencrypt',
            'certificate' => 'TEST_CERTIFICATE_CONTENT',
            'private_key' => 'TEST_KEY_CONTENT',
            'valid_from' => now(),
            'valid_to' => now()->addDays(90),
            'status' => 'active',
        ]);

        $responseCrt = $this->actingAs($this->adminUser)
            ->get(route('admin.security.ssl.download', ['sslCertificate' => $cert->id, 'part' => 'crt']));

        $responseCrt->assertStatus(200);
        $this->assertStringContainsString('TEST_CERTIFICATE_CONTENT', $responseCrt->getContent());

        $responseDel = $this->actingAs($this->adminUser)
            ->delete(route('admin.security.ssl.destroy', $cert->id));

        $responseDel->assertRedirect();
        $responseDel->assertSessionHas('success');

        $this->assertDatabaseMissing('ssl_certificates', [
            'id' => $cert->id,
        ]);
    }
}
