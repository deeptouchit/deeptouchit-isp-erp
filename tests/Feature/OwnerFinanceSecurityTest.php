<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OwnerFinanceSecurityTest extends TestCase
{
    public function test_finance_read_routes_have_view_capabilities(): void
    {
        $this->assertRouteMiddleware('owner.billing.index', 'owner.capability:billing.view');
        $this->assertRouteMiddleware('owner.subscriptions.index', 'owner.capability:subscriptions.view');
        $this->assertRouteMiddleware('owner.payments.index', 'owner.capability:payments.view');
        $this->assertRouteMiddleware('owner.transactions.index', 'owner.capability:transactions.view');
    }

    public function test_finance_mutation_routes_have_manage_capabilities(): void
    {
        $this->assertRouteMiddleware('owner.billing.store', 'owner.capability:billing.manage');
        $this->assertRouteMiddleware('owner.billing.update', 'owner.capability:billing.manage');
        $this->assertRouteMiddleware('owner.subscriptions.store', 'owner.capability:subscriptions.manage');
        $this->assertRouteMiddleware('owner.subscriptions.renew', 'owner.capability:subscriptions.manage');
        $this->assertRouteMiddleware('owner.subscriptions.status', 'owner.capability:subscriptions.manage');
    }

    private function assertRouteMiddleware(string $name, string $expected): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route {$name} must exist.");
        $this->assertContains($expected, $route->middleware(), "Route {$name} must use {$expected}.");
    }
}
