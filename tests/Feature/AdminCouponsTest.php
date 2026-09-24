<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\HostingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Coupon $coupon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->coupon = Coupon::create([
            'code' => 'TESTPROMO20',
            'type' => 'percentage',
            'value' => 20.00,
            'applies_to' => 'all',
            'min_order_amount' => 100.00,
            'max_discount_amount' => 500.00,
            'usage_limit' => 50,
            'usage_count' => 5,
            'user_limit' => 1,
            'is_active' => true,
            'description' => 'Test Promo Coupon',
        ]);
    }

    public function test_admin_can_view_coupons_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.billing.coupons'));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('Admin/Billing/Coupons/Index')
            ->has('coupons')
            ->has('stats')
            ->has('plans')
        );
    }

    public function test_admin_can_create_coupon(): void
    {
        $plan = HostingPlan::create([
            'name' => 'Starter Cloud NVMe',
            'slug' => 'starter-cloud-nvme',
            'disk_space' => 2048,
            'bandwidth' => 20480,
            'max_domains' => 1,
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.coupons.store'), [
                'code' => 'EIDSPECIAL50',
                'type' => 'fixed',
                'value' => 50.00,
                'applies_to' => 'specific_plans',
                'plan_ids' => [$plan->id],
                'min_order_amount' => 200.00,
                'max_discount_amount' => 50.00,
                'usage_limit' => 100,
                'user_limit' => 1,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addMonth()->toDateString(),
                'description' => 'Flat 50 BDT Discount',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'EIDSPECIAL50',
            'type' => 'fixed',
            'value' => 50.00,
            'applies_to' => 'specific_plans',
        ]);
    }

    public function test_admin_can_view_single_coupon_json(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.billing.coupons.show', $this->coupon->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'coupon' => [
                'id',
                'code',
                'type',
                'value',
            ],
            'is_valid',
        ]);
    }

    public function test_admin_can_update_coupon(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.billing.coupons.update', $this->coupon->id), [
                'code' => 'TESTPROMO20',
                'type' => 'percentage',
                'value' => 25.00,
                'applies_to' => 'all',
                'description' => 'Updated Promo Description',
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'id' => $this->coupon->id,
            'value' => 25.00,
            'description' => 'Updated Promo Description',
        ]);
    }

    public function test_admin_can_toggle_coupon_status(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.coupons.toggle-status', $this->coupon->id));

        $response->assertRedirect();
        $this->assertFalse((bool)$this->coupon->fresh()->is_active);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.billing.coupons.toggle-status', $this->coupon->id));

        $response->assertRedirect();
        $this->assertTrue((bool)$this->coupon->fresh()->is_active);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.billing.coupons.destroy', $this->coupon->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('coupons', ['id' => $this->coupon->id]);
    }
}
