<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebhooksTest extends TestCase
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

        Webhook::create([
            'user_id' => $this->adminUser->id,
            'name' => 'Automated WHMCS Provisioner Sync',
            'url' => 'https://billing.example.com/webhooks/provisioner',
            'secret' => 'whsec_test_secret_123456789',
            'events' => ['hosting.account.created', 'invoice.paid'],
            'content_type' => 'application/json',
            'verify_ssl' => true,
            'status' => 'active',
            'success_count' => 10,
            'failure_count' => 0,
        ]);
    }

    public function test_admin_can_view_webhooks_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.webhooks'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Webhooks/Index')
            ->has('webhooks')
            ->has('stats')
            ->has('filters')
        );
    }

    public function test_admin_can_filter_webhooks_by_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.webhooks', ['status' => 'active']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Webhooks/Index')
            ->where('filters.status', 'active')
        );
    }

    public function test_admin_can_search_webhooks(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.webhooks', ['search' => 'Automated WHMCS']));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Api/Webhooks/Index')
            ->where('filters.search', 'Automated WHMCS')
        );
    }

    public function test_admin_can_create_new_webhook_with_hmac_secret(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.webhooks.store'), [
                'name' => 'Slack Security Channel',
                'url' => 'https://hooks.slack.com/services/T123/B456/Z789',
                'events' => ['security.ip_banned'],
                'content_type' => 'application/json',
                'verify_ssl' => true,
            ]);

        $response->assertRedirect(route('admin.api.webhooks'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('webhooks', [
            'name' => 'Slack Security Channel',
            'url' => 'https://hooks.slack.com/services/T123/B456/Z789',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_webhook(): void
    {
        $hook = Webhook::first();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.api.webhooks.update', $hook->id), [
                'name' => 'WHMCS Provisioner Updated',
                'url' => 'https://billing.example.com/webhooks/updated',
                'events' => ['*'],
                'content_type' => 'application/json',
                'verify_ssl' => false,
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.api.webhooks'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('webhooks', [
            'id' => $hook->id,
            'name' => 'WHMCS Provisioner Updated',
            'verify_ssl' => false,
        ]);
    }

    public function test_admin_can_test_ping_webhook(): void
    {
        $hook = Webhook::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.webhooks.test', $hook->id));

        $response->assertRedirect(route('admin.api.webhooks'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('webhook_deliveries', [
            'webhook_id' => $hook->id,
            'event' => 'ping',
            'response_code' => 200,
        ]);
    }

    public function test_admin_can_toggle_webhook_status(): void
    {
        $hook = Webhook::first();

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.api.webhooks.toggle-status', $hook->id));

        $response->assertRedirect(route('admin.api.webhooks'));
        $response->assertSessionHas('success');

        $this->assertEquals('paused', $hook->fresh()->status);
    }

    public function test_admin_can_delete_webhook(): void
    {
        $hook = Webhook::first();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.api.webhooks.destroy', $hook->id));

        $response->assertRedirect(route('admin.api.webhooks'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('webhooks', [
            'id' => $hook->id,
        ]);
    }

    public function test_admin_can_fetch_webhook_deliveries(): void
    {
        $hook = Webhook::first();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.api.webhooks.deliveries', $hook->id));

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => [
                'id',
                'webhook_id',
                'event',
                'payload',
                'response_code',
                'response_time_ms',
                'status',
            ],
        ]);
    }
}
