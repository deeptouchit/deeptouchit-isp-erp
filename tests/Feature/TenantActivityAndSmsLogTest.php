<?php

namespace Tests\Feature;

use App\Models\SaasPlan;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\User;
use App\Services\Sms\SmsTrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantActivityAndSmsLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected SaasPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'email' => 'superadmin@somitysoft.com',
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->plan = SaasPlan::create([
            'name' => 'Enterprise ISP Plan',
            'slug' => 'enterprise-isp',
            'monthly_price' => 5000.00,
            'otc_charge' => 0.00,
            'customer_limit' => 5000,
            'is_active' => true,
        ]);
    }

    public function test_tenant_creation_logs_audit_trail(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.tenants.store'), [
            'name' => 'Apex Fiber Internet',
            'company_name' => 'Apex Networks Ltd',
            'slug' => 'apex-fiber',
            'phone' => '01711223344',
            'email' => 'info@apexfiber.com',
            'saas_plan_id' => $this->plan->id,
            'admin_name' => 'Apex Admin',
            'admin_email' => 'admin@apexfiber.com',
            'admin_password' => 'secret1234',
            'duration_months' => 3,
        ]);

        $response->assertRedirect(route('owner.tenants.index'));

        $tenant = Tenant::where('slug', 'apex-fiber')->first();
        $this->assertNotNull($tenant);

        // Verify audit log created
        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'tenant_created',
        ]);
    }

    public function test_tenant_status_toggle_and_impersonation_logs_activity(): void
    {
        $tenant = Tenant::create([
            'name' => 'Delta Broadband',
            'company_name' => 'Delta Broadband Ltd',
            'slug' => 'delta-bb',
            'phone' => '01811223344',
            'email' => 'info@deltabb.com',
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $adminUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'isp_admin',
            'email' => 'admin@deltabb.com',
        ]);

        // 1. Toggle status
        $this->actingAs($this->owner)->post(route('owner.tenants.toggle-status', $tenant));
        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'status_toggled',
        ]);

        // 2. Impersonate
        $this->actingAs($this->owner)->get(route('owner.tenants.impersonate', $tenant));
        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'impersonated',
        ]);
    }

    public function test_wallet_adjustment_records_audit_trail(): void
    {
        $tenant = Tenant::create([
            'name' => 'Cosmo Net',
            'company_name' => 'Cosmo Net Ltd',
            'slug' => 'cosmo-net',
            'phone' => '01911223344',
            'email' => 'info@cosmonet.com',
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->owner)->post(route('owner.billing.wallets.adjust', $tenant), [
            'type' => 'credit',
            'amount' => 1500.00,
            'description' => 'Security Deposit Advance via Bank',
        ]);

        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'wallet_adjusted',
        ]);
    }

    public function test_sms_parts_and_cost_calculation(): void
    {
        $smsTracker = app(SmsTrackerService::class);

        // GSM-7 short message (1 part)
        $gsm1 = $smsTracker->calculateParts("Your invoice #INV-001 of BDT 1,000 is ready.");
        $this->assertEquals(1, $gsm1['parts_count']);
        $this->assertFalse($gsm1['is_unicode']);

        // Bangla Unicode message (1 part vs 2 parts)
        $bangla1 = $smsTracker->calculateParts("আপনার মাসিক বিল ১০০০ টাকা পরিশোধ হয়েছে। ধন্যবাদ।");
        $this->assertTrue($bangla1['is_unicode']);
        $this->assertEquals(1, $bangla1['parts_count']);

        // Record SMS in database
        $tenant = Tenant::create([
            'name' => 'SkyLine Net',
            'company_name' => 'SkyLine Ltd',
            'slug' => 'skyline-net',
            'phone' => '01611223344',
            'email' => 'info@skylinenet.com',
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $log = $smsTracker->recordSms(
            $tenant,
            '01712345678',
            'Your SaaS subscription has renewed successfully.',
            'billing_reminder',
            'BulkSMS BD',
            'delivered',
            0.3500
        );

        $this->assertInstanceOf(SmsLog::class, $log);
        $this->assertEquals(0.3500, $log->total_cost);
        $this->assertEquals('delivered', $log->status);

        $this->assertDatabaseHas('sms_logs', [
            'tenant_id' => $tenant->id,
            'recipient_phone' => '01712345678',
            'sms_type' => 'billing_reminder',
            'status' => 'delivered',
        ]);
    }
}
