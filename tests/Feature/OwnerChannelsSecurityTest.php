<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OwnerChannelsSecurityTest extends TestCase
{
    public function test_channel_read_routes_have_view_capabilities(): void
    {
        $this->assertRouteMiddleware('owner.payment-gateways.index', 'owner.capability:gateways.view');
        $this->assertRouteMiddleware('owner.sms-gateways.index', 'owner.capability:sms.view');
        $this->assertRouteMiddleware('owner.mail-settings.index', 'owner.capability:mail.view');
        $this->assertRouteMiddleware('owner.activity-logs.index', 'owner.capability:activity-logs.view');
        $this->assertRouteMiddleware('owner.sms-logs.index', 'owner.capability:sms.view');
    }

    public function test_channel_mutation_and_test_routes_are_protected_and_unique(): void
    {
        $this->assertRouteMiddleware('owner.payment-gateways.update', 'owner.capability:gateways.manage');
        $this->assertRouteMiddleware('owner.payment-gateways.test', 'owner.capability:gateways.test');
        $this->assertRouteMiddleware('owner.sms-gateways.update', 'owner.capability:sms.manage');
        $this->assertRouteMiddleware('owner.sms-gateways.test', 'owner.capability:sms.test');
        $this->assertRouteMiddleware('owner.sms-gateways.balance', 'owner.capability:sms.test');
        $this->assertRouteMiddleware('owner.mail-settings.update', 'owner.capability:mail.manage');
        $this->assertRouteMiddleware('owner.mail-settings.test', 'owner.capability:mail.test');

        $mailTestRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->getName() === 'owner.mail-settings.test');

        $this->assertCount(1, $mailTestRoutes);
    }

    private function assertRouteMiddleware(string $name, string $expected): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route {$name} must exist.");
        $this->assertContains($expected, $route->middleware(), "Route {$name} must use {$expected}.");
    }
}
