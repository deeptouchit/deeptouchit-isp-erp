<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request and apply user selected locale (English / বাংলা).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allowed locales
        $allowedLocales = ['bn', 'en'];

        // Determine locale from session, fallback to config or default 'bn'
        $locale = Session::get('locale', config('app.locale', 'bn'));

        if (!in_array($locale, $allowedLocales)) {
            $locale = 'bn';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
