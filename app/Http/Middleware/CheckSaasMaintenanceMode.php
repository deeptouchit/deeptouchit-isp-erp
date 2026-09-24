<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSaasMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $isMaintenance = Setting::get('maintenance_mode', '0');
        } catch (\Throwable $e) {
            $isMaintenance = '0';
        }

        if ($isMaintenance !== '1') {
            return $next($request);
        }

        // Always allow Platform Owner routes, API, and login console
        if ($request->is('owner') || $request->is('owner/*') || $request->routeIs('owner.*') || $request->is('up')) {
            return $next($request);
        }

        // Check if client IP is whitelisted
        try {
            $whitelistStr = Setting::get('owner_ip_whitelist', '') ?: Setting::get('ip_whitelist', '');
            if (!empty($whitelistStr)) {
                $whitelistedIps = array_filter(array_map('trim', explode("\n", str_replace(["\r", ','], "\n", $whitelistStr))));
                $clientIp = $request->ip();
                if (in_array($clientIp, $whitelistedIps)) {
                    return $next($request);
                }
            }

            // Fetch maintenance customization
            $maintenanceMessage = Setting::get('maintenance_message', 'We are currently performing scheduled platform upgrades. We will be back online shortly.');
            $companyName = Setting::get('company_name') ?: Setting::get('app_name', 'SomitySoft SaaS');
            $supportEmail = Setting::get('support_email', 'support@somitysoft.com');
            $supportPhone = Setting::get('support_phone', '+880 1700-000000');
            $appLogo = Setting::get('app_logo', '');
        } catch (\Throwable $e) {
            $maintenanceMessage = 'We are currently performing scheduled platform upgrades. We will be back online shortly.';
            $companyName = 'SomitySoft SaaS';
            $supportEmail = 'support@somitysoft.com';
            $supportPhone = '+880 1700-000000';
            $appLogo = '';
        }

        return response()->view('errors.maintenance', compact(
            'maintenanceMessage',
            'companyName',
            'supportEmail',
            'supportPhone',
            'appLogo'
        ), 503);
    }
}
