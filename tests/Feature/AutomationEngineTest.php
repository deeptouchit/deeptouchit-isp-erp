<?php

namespace Tests\Feature;

use App\Models\AutomationLog;
use App\Models\SaasPlan;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\Billing\AutomationEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_automation_hub_and_update_policies(): void
    {
        $owner = User::create([
            'name' => 'Super Admin Automation',
            'email' => 'admin_auto@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // 1. Access Automation Hub
        $res = $this->get('/owner/automation');
        $res->assertStatus(200);
        $res->assertSee('SaaS Automation');
        $res->assertSee('Crontab Setup Command');

        // 2. Update Automation Policies
        $updateRes = $this->post('/owner/automation/policy', [
            'automation_invoice_lead_days' => 5,
            'automation_grace_period_days' => 4,
            'automation_auto_suspend_enabled' => '1',
            'automation_auto_wallet_renew_enabled' => '1',
            'automation_cron_run_hour' => '01:00',
        ]);

        $updateRes->assertRedirect();
        $updateRes->assertSessionHas('success');

        $this->assertEquals('5', Setting::get('automation_invoice_lead_days'));
        $this->assertEquals('4', Setting::get('automation_grace_period_days'));
        $this->assertEquals('1', Setting::get('automation_auto_suspend_enabled'));
        $this->assertEquals('01:00', Setting::get('automation_cron_run_hour'));
    }

    public function test_automation_engine_runs_with_idempotency_and_logs_audit_trail(): void
    {
        $plan = SaasPlan::create([
            'code' => 'P1',
            'name' => 'Starter ISP',
            'slug' => 'starter-isp',
            'monthly_price' => 1200,
            'otc_charge' => 0,
            'customer_limit' => 300,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Omega Net',
            'company_name' => 'Omega Net Ltd',
            'slug' => 'omeganet',
            'phone' => '01711000999',
            'email' => 'info@omeganet.com',
            'saas_plan_id' => $plan->id,
            'status' => 'active',
        ]);

        // Subscription expiring in 2 days
        $subscription = TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subMonth(),
            'current_period_start' => Carbon::now()->subMonth(),
            'current_period_end' => Carbon::now()->addDays(2),
            'next_billing_date' => Carbon::now()->addDays(2),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $engine = app(AutomationEngineService::class);

        // First Run: Should generate 1 invoice
        $result1 = $engine->runFullEngine('test');
        $this->assertEquals('success', $result1['status']);
        $this->assertEquals(1, $result1['metrics']['invoices_generated']);

        // Check Audit Log Created
        $this->assertDatabaseHas('automation_logs', [
            'task_name' => 'master_billing_engine',
            'triggered_by' => 'test',
            'status' => 'success',
        ]);

        // Second Run: Should generate 0 invoices (Idempotent, no duplicate invoice)
        $result2 = $engine->runFullEngine('test');
        $this->assertEquals('success', $result2['status']);
        $this->assertEquals(0, $result2['metrics']['invoices_generated']);
    }

    public function test_owner_can_manually_dispatch_automation_tasks(): void
    {
        $owner = User::create([
            'name' => 'Ops Admin',
            'email' => 'ops@somitysoft.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        // 1. Trigger Full Engine
        $res = $this->post('/owner/automation/dispatch/full_engine');
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // 2. Trigger Invoices Only
        $res = $this->post('/owner/automation/dispatch/invoices_only');
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // 3. Trigger Suspensions Only
        $res = $this->post('/owner/automation/dispatch/suspensions_only');
        $res->assertRedirect();
        $res->assertSessionHas('success');

        // 4. Trigger Heartbeat
        $res = $this->post('/owner/automation/dispatch/heartbeat_now');
        $res->assertRedirect();
        $res->assertSessionHas('success');
    }
}
