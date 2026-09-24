<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGatewaysTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected PaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->gateway = PaymentGateway::firstOrCreate(
            ['slug' => 'bkash'],
            [
                'name' => 'bKash Merchant PGW',
                'category' => 'mfs',
                'credentials' => [
                    'app_key' => 'test_key',
                    'app_secret' => 'test_secret',
                    'username' => 'test_user',
                    'password' => 'test_pass',
                ],
                'mode' => 'sandbox',
                'fee_type' => 'percentage',
                'fee_value' => 1.50,
                'min_amount' => 10.00,
                'max_amount' => 50000.00,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }

    public function test_admin_can_view_gateways_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.gateways'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Gateways/Index')
            ->has('gateways')
            ->has('stats')
        );
    }

    public function test_admin_can_update_gateway_credentials_and_mode(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.billing.gateways.update', $this->gateway->id), [
                'name' => 'bKash Production PGW',
                'mode' => 'live',
                'fee_type' => 'percentage',
                'fee_value' => 1.75,
                'min_amount' => 20.00,
                'max_amount' => 100000.00,
                'instructions' => 'Updated checkout instructions for bKash',
                'is_active' => true,
                'credentials' => [
                    'app_key' => 'new_live_key',
                    'app_secret' => 'new_live_secret',
                    'username' => 'live_merchant',
                    'password' => 'new_password',
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->gateway->refresh();
        $this->assertEquals('bKash Production PGW', $this->gateway->name);
        $this->assertEquals('live', $this->gateway->mode);
        $this->assertEquals(1.75, (float)$this->gateway->fee_value);
        $this->assertEquals('new_live_key', $this->gateway->credentials['app_key']);
    }

    public function test_admin_can_toggle_gateway_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.gateways.toggle-status', $this->gateway->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->gateway->fresh()->is_active);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.gateways.toggle-status', $this->gateway->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->gateway->fresh()->is_active);
    }

    public function test_admin_can_test_gateway_connection(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.billing.gateways.test-connection', $this->gateway->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'gateway',
            'mode',
            'latency_ms',
            'message',
            'ready',
        ]);
    }
}
