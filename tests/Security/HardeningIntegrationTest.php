<?php

namespace Tests\Security;

use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HardeningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $slug): Tenant
    {
        return Tenant::create(['name' => $slug, 'company_name' => $slug, 'slug' => $slug, 'status' => 'active']);
    }

    public function test_user_without_membership_is_not_assigned_first_tenant(): void
    {
        $this->tenant('first');
        $user = User::factory()->create(['tenant_id' => null, 'role' => 'isp_admin', 'status' => 'active']);
        $this->actingAs($user)->getJson('/admin/dashboard')->assertForbidden();
        $this->assertNull($user->fresh()->tenant_id);
    }

    public function test_other_tenants_router_cannot_be_deleted(): void
    {
        $first = $this->tenant('first');
        $second = $this->tenant('second');
        $user = User::factory()->create(['tenant_id' => $first->id, 'role' => 'isp_admin', 'status' => 'active']);
        $router = TenantRouter::create(['tenant_id' => $second->id, 'name' => 'Other router', 'ip_address' => '192.0.2.2', 'username' => 'audit', 'password' => 'audit-only', 'status' => 'offline']);
        $this->actingAs($user)->deleteJson(route('tenant.network.mikrotik.destroy', $router->id))->assertNotFound();
        $this->assertDatabaseHas('tenant_routers', ['id' => $router->id, 'tenant_id' => $second->id]);
    }

    public function test_inactive_owner_cannot_log_in(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'status' => 'inactive', 'password' => 'audit-password']);
        $this->postJson(route('owner.login.submit'), ['email' => $user->email, 'password' => 'audit-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_owner_existing_session_cannot_access_settings(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'status' => 'inactive']);
        $this->actingAs($user)->get(route('owner.settings.index'))->assertRedirect(route('owner.login'));
        $this->assertGuest();
    }

    public function test_owner_login_is_throttled_before_password_check(): void
    {
        $email = 'audit-owner@example.test';
        $key = 'owner-login:'.$email.'|127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
        }
        $this->postJson(route('owner.login.submit'), ['email' => $email, 'password' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_owner_cannot_upload_script_as_branding(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $file = UploadedFile::fake()->createWithContent('audit.php', '<?php echo "not executable";');
        $this->actingAs($owner)->postJson(route('owner.settings.update'), ['app_logo' => $file])->assertUnprocessable()->assertJsonValidationErrors('app_logo');
        $this->assertFileDoesNotExist(public_path('uploads/branding/audit.php'));
    }
}
