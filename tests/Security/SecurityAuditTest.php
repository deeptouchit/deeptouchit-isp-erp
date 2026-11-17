<?php

namespace Tests\Security;

use App\Http\Middleware\CheckTenantPermission;
use App\Models\User;
use App\Services\Payment\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    private function authorizeNetworkRoute(string $routeName, array $grants): int
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('isOwner')->andReturn(false);
        $user->shouldReceive('isIspAdmin')->andReturn(false);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => in_array($permission, $grants, true));
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        $request = Request::create('/audit');
        $request->headers->set('Accept', 'application/json');
        $route = app('router')->getRoutes()->getByName($routeName);
        foreach ($route->gatherMiddleware() as $middleware) {
            if (str_starts_with($middleware, 'permission:')) {
                $response = (new CheckTenantPermission)->handle($request, fn () => response()->json([]), ...explode(',', substr($middleware, 11)));
                if ($response->getStatusCode() !== 200) {
                    return $response->getStatusCode();
                }
            }
        }
        return 200;
    }

    public function test_router_permission_alone_cannot_delete_router(): void
    {
        $this->assertSame(403, $this->authorizeNetworkRoute('tenant.network.mikrotik.destroy', ['network.routers']));
    }

    public function test_explicit_router_delete_grant_is_accepted(): void
    {
        $this->assertSame(200, $this->authorizeNetworkRoute('tenant.network.mikrotik.destroy', ['network.routers', 'network.delete']));
    }

    public function test_ip_pool_grant_can_access_its_own_module(): void
    {
        $this->assertSame(200, $this->authorizeNetworkRoute('tenant.network.ip-pools', ['network.ip_pools']));
    }

    private function signedRequest(?int $timestamp, string $secret = 'audit-only-secret'): Request
    {
        $payload = '{"transaction_reference":"audit-only","status":"success"}';
        $request = Request::create('/api/webhooks/payment/bkash', 'POST', [], [], [], [], $payload);
        if ($timestamp !== null) {
            $request->headers->set('X-Timestamp', (string) $timestamp);
        }
        $request->headers->set('X-Signature', hash_hmac('sha256', $timestamp === null ? $payload : $timestamp.'.'.$payload, $secret));
        Cache::put('setting__bkash_webhook_secret', 'audit-only-secret');
        return $request;
    }

    public function test_valid_signed_payment_is_accepted(): void
    {
        $this->assertTrue((new PaymentWebhookService)->verifySignature($this->signedRequest(time()), 'bkash'));
    }

    public function test_wrong_payment_signature_is_rejected(): void
    {
        $this->assertFalse((new PaymentWebhookService)->verifySignature($this->signedRequest(time(), 'wrong-secret'), 'bkash'));
    }

    public function test_expired_payment_signature_is_rejected(): void
    {
        $this->assertFalse((new PaymentWebhookService)->verifySignature($this->signedRequest(time() - 600), 'bkash'));
    }

    public function test_payment_signature_requires_replay_timestamp(): void
    {
        $this->assertFalse((new PaymentWebhookService)->verifySignature($this->signedRequest(null), 'bkash'));
    }

    public function test_unsigned_development_flag_cannot_enable_unsigned_production_payments(): void
    {
        $this->app->instance('env', 'production');
        Cache::put('setting__payment_webhook_allow_unsigned_dev', '1');
        $this->assertFalse((new PaymentWebhookService)->verifySignature(Request::create('/audit', 'POST'), 'bkash'));
    }

    public function test_ip_pool_permission_does_not_grant_router_delete_access(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('isOwner')->andReturn(false);
        $user->shouldReceive('isIspAdmin')->andReturn(false);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($permission) => $permission === 'network.ip_pools');
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        $route = app('router')->getRoutes()->getByName('tenant.network.mikrotik.destroy');
        $permissionMiddleware = collect($route->gatherMiddleware())->first(fn ($entry) => str_starts_with($entry, 'permission:'));
        $this->assertNotNull($permissionMiddleware);
        $permissions = explode(',', substr($permissionMiddleware, strlen('permission:')));
        $request = Request::create('/admin/network/mikrotik/1', 'DELETE');
        $request->headers->set('Accept', 'application/json');
        $response = (new CheckTenantPermission)->handle($request, fn () => response()->json(['reached_controller' => true]), ...$permissions);
        $this->assertSame(403, $response->getStatusCode());
    }
}
