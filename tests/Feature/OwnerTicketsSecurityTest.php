<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OwnerTicketsSecurityTest extends TestCase
{
    public function test_ticket_read_routes_are_capability_protected(): void
    {
        $this->assertRouteHas('owner.tickets.index', 'owner.capability:tickets.view');
        $this->assertRouteHas('owner.tickets.show', 'owner.capability:tickets.view');
    }

    public function test_ticket_mutations_are_manage_protected(): void
    {
        foreach (['owner.tickets.reply', 'owner.tickets.status', 'owner.tickets.priority', 'owner.tickets.destroy'] as $routeName) {
            $this->assertRouteHas($routeName, 'owner.capability:tickets.manage');
        }
    }

    private function assertRouteHas(string $name, string $middleware): void
    {
        $route = Route::getRoutes()->getByName($name);
        $this->assertNotNull($route, "Route {$name} must exist.");
        $this->assertContains($middleware, $route->middleware());
    }
}
