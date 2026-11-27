<?php

namespace Tests\Feature;

use App\Models\OwnerSmsGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerSmsGatewayManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_owner_can_create_and_activate_only_one_sms_gateway(): void
    {
        $this->actingAs($this->owner());

        $this->post(route('owner.sms-gateways.store'), [
            'name' => 'Primary BulkSMS',
            'provider' => 'bulksmsbd',
            'sender_id' => 'SomitySoft',
            'api_key' => 'secret-key',
            'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $first = OwnerSmsGateway::first();
        $this->assertTrue($first->is_active);
        $this->assertSame('secret-key', $first->credentials['api_key']);

        $this->post(route('owner.sms-gateways.store'), [
            'name' => 'Backup Gateway',
            'provider' => 'greenweb',
            'api_key' => 'backup-key',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(1, OwnerSmsGateway::where('is_active', true)->count());
        $this->assertTrue(OwnerSmsGateway::where('name', 'Backup Gateway')->first()->is_active);
        $this->assertFalse($first->fresh()->is_active);
    }

    public function test_owner_can_update_and_delete_a_gateway(): void
    {
        $this->actingAs($this->owner());
        $gateway = OwnerSmsGateway::create([
            'name' => 'Old Gateway',
            'provider' => 'bulksmsbd',
            'credentials' => ['api_key' => 'old-key'],
        ]);

        $this->put(route('owner.sms-gateways.gateway.update', $gateway), [
            'name' => 'Updated Gateway',
            'provider' => 'custom_http',
            'api_key' => '',
            'custom_url' => 'https://example.com/sms?to={number}',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Updated Gateway', $gateway->fresh()->name);
        $this->assertSame('old-key', $gateway->fresh()->credentials['api_key']);

        $this->delete(route('owner.sms-gateways.gateway.delete', $gateway))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('owner_sms_gateways', ['id' => $gateway->id]);
    }
}
