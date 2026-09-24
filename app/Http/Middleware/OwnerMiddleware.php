<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('owner.login');
        }

        $user = auth()->user();
        if (!$user->isOwner()) {
            auth()->logout();
            return redirect()->route('owner.login')->with('error', 'আপনার প্ল্যাটফর্ম ওনার (SaaS Super Admin) অ্যাক্সেস নেই।');
        }

        return $next($request);
    }
}
