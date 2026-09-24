<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 0. Dynamic RBAC Authorization Gate
        Gate::before(function ($user, string $ability) {
            if ($user && method_exists($user, 'isOwner') && ($user->isOwner() || $user->isIspAdmin())) {
                return true;
            }

            if ($user && method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        // 1. Dynamic System Timezone Boot
        try {
            if (Schema::hasTable('settings')) {
                $timezone = Setting::whereNull('tenant_id')->where('key', 'app_timezone')->value('value');
                if ($timezone) {
                    config(['app.timezone' => $timezone]);
                    date_default_timezone_set($timezone);
                }
            }
        } catch (\Throwable $e) {
            // Fail-safe during early migrations
        }

        // 2. Share global settings across all blade views
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('settings')) {
                    $globalSettings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();
                    $currencySymbol = $globalSettings['currency_symbol'] ?? '৳';
                    $currencyCode = $globalSettings['currency_code'] ?? 'BDT';
                    $currencyPosition = $globalSettings['currency_position'] ?? 'left';
                    $currencyDecimals = (int)($globalSettings['currency_decimals'] ?? 2);
                    $thousandSep = $globalSettings['thousand_separator'] ?? ',';
                    if ($thousandSep === 'space') {
                        $thousandSep = ' ';
                    }
                    $decimalSep = $globalSettings['decimal_separator'] ?? '.';

                    $view->with([
                        'globalSettings' => $globalSettings,
                        'currencySymbol' => $currencySymbol,
                        'currencyCode' => $currencyCode,
                        'currencyPosition' => $currencyPosition,
                        'currencyDecimals' => $currencyDecimals,
                        'thousandSeparator' => $thousandSep,
                        'decimalSeparator' => $decimalSep,
                    ]);
                }
            } catch (\Throwable $e) {
                // Fail-safe during early migrations
            }
        });

        // 3. Custom Blade directive for unified dynamic currency formatting
        Blade::directive('currency', function ($expression) {
            return "<?php 
                \$currSym = \$currencySymbol ?? '৳';
                \$currPos = \$currencyPosition ?? 'left';
                \$currDec = \$currencyDecimals ?? 2;
                \$thSep = \$thousandSeparator ?? ',';
                \$decSep = \$decimalSeparator ?? '.';
                \$_val = ($expression);
                \$amt = is_numeric(\$_val) ? number_format((float)\$_val, \$currDec, \$decSep, \$thSep) : \$_val;
                echo (\$currPos === 'right') ? (\$amt . '&nbsp;' . \$currSym) : (\$currSym . '&nbsp;' . \$amt);
            ?>";
        });

        // 4. Custom Blade directive for date formatting from settings
        Blade::directive('date', function ($expression) {
            return "<?php 
                \$_rawDt = ($expression);
                if (\$_rawDt) {
                    \$dFmt = \$globalSettings['date_format'] ?? 'd M Y';
                    echo \Carbon\Carbon::parse(\$_rawDt)->format(\$dFmt);
                }
            ?>";
        });

        // 5. Custom Blade directive for datetime formatting from settings
        Blade::directive('datetime', function ($expression) {
            return "<?php 
                \$_rawDt = ($expression);
                if (\$_rawDt) {
                    \$dFmt = \$globalSettings['date_format'] ?? 'd M Y';
                    \$tFmt = \$globalSettings['time_format'] ?? 'h:i A';
                    echo \Carbon\Carbon::parse(\$_rawDt)->format(\$dFmt . ' ' . \$tFmt);
                }
            ?>";
        });
    }
}
