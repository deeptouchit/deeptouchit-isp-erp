<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerCapability
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        abort_unless($user && $user->isOwner() && $user->status === 'active', 403);

        $capabilities = config('owner_capabilities', []);
        abort_unless(in_array($capability, $capabilities, true), 403);

        // A null capability set preserves legacy full-owner access during rollout;
        // once assigned, the database set becomes the source of truth.
        $assigned = $user->owner_capabilities;
        abort_unless($assigned === null || in_array($capability, $assigned, true), 403);

        return $next($request);
    }
}
