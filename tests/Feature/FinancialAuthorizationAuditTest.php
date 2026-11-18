<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantCustomer;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\Audit\FinancialAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FinancialAuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_view_permission_cannot_collect_payment(): void
    {
        [$tenant, $customer] = $this->customerFixture();
        TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'customer_viewer',
            'display_name' => 'Customer Viewer',
            'status' => 'active',
            'permissions' => ['customers.view'],
        ]);
        $user = $this->user($tenant, 'customer_viewer');

        $this->actingAs($user)
            ->postJson(route('tenant.customers.pay-bill', $customer->id), [
                'payment_mode' => 'due',
                'amount' => 100,
                'payment_method' => 'cash',
                'request_key' => 'unauthorized-payment-request',
            ])
            ->assertForbidden();
    }

    public function test_financial_audit_keeps_traceability_and_removes_sensitive_metadata(): void
    {
        [$tenant] = $this->customerFixture();
        $user = $this->user($tenant, 'isp_manager');
        $request = Request::create('/audit-test', 'POST', [
            'request_key' => 'audit-request-123',
            'notes' => 'must not be persisted',
        ]);
        $request->headers->set('X-Request-ID', 'audit-request-123');

        $this->actingAs($user);
        $log = app(FinancialAuditService::class)->record(
            $tenant,
            'CUSTOMER_PAYMENT_COLLECTED',
            'Test financial audit event',
            [
                'payment_id' => 42,
                'old_due' => 500,
                'new_due' => 0,
                'password' => 'never-store-this',
                'secret' => 'never-store-this',
                'notes' => 'never-store-this',
                'reason' => 'never-store-this',
                'request_key' => 'never-store-this',
            ],
            $request,
        );

        $metadata = $log->fresh()->metadata;
        $this->assertSame(42, $metadata['payment_id']);
        $this->assertSame(500, $metadata['old_due']);
        $this->assertSame(0, $metadata['new_due']);
        $this->assertSame(substr(hash('sha256', 'audit-request-123'), 0, 20), $metadata['request_reference']);
        $this->assertArrayNotHasKey('password', $metadata);
        $this->assertArrayNotHasKey('secret', $metadata);
        $this->assertArrayNotHasKey('notes', $metadata);
        $this->assertArrayNotHasKey('reason', $metadata);
        $this->assertArrayNotHasKey('request_key', $metadata);
        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame('CUSTOMER_PAYMENT_COLLECTED', TenantActivityLog::findOrFail($log->id)->event_type);
    }

    private function customerFixture(): array
    {
        $tenant = Tenant::create([
            'name' => 'Audit ISP',
            'company_name' => 'Audit ISP',
            'slug' => 'audit-isp',
            'status' => 'active',
        ]);
        $customer = TenantCustomer::create([
            'tenant_id' => $tenant->id,
            'customer_id' => 'AUD1001',
            'name' => 'Audit Customer',
            'username' => 'audit-customer',
            'password' => 'test-password',
            'phone' => '01700000000',
            'monthly_bill' => 500,
            'due_amount' => 500,
            'status' => 'active',
        ]);

        return [$tenant, $customer];
    }

    private function user(Tenant $tenant, string $role): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Audit User',
            'email' => $role . '@audit.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
