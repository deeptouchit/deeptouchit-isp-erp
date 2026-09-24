<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Validation\Rule;

class AdminLocalizationSettingController extends Controller
{
    /**
     * Standard recommended defaults for Localization & Currency Settings.
     */
    private const DEFAULTS = [
        'currency_code' => 'BDT',
        'currency_symbol' => '৳',
        'currency_position' => 'before',
        'decimal_separator' => '.',
        'thousand_separator' => ',',
        'decimal_precision' => 2,
        'timezone' => 'Asia/Dhaka',
        'date_format' => 'd/m/Y',
        'time_format' => '12h',
        'first_day_of_week' => 'sunday',
        'default_locale' => 'en',
        'allow_client_language' => true,
        'rtl_support' => false,
        'exchange_rates' => [
            ['code' => 'USD', 'symbol' => '$', 'rate' => 0.0084, 'enabled' => true],
            ['code' => 'EUR', 'symbol' => '€', 'rate' => 0.0078, 'enabled' => true],
            ['code' => 'GBP', 'symbol' => '£', 'rate' => 0.0067, 'enabled' => false],
            ['code' => 'INR', 'symbol' => '₹', 'rate' => 0.73, 'enabled' => true],
        ],
    ];

    /**
     * Curated standard timezones list.
     */
    private const POPULAR_TIMEZONES = [
        'Asia/Dhaka' => '(UTC+06:00) Dhaka / Bangladesh Standard Time',
        'UTC' => '(UTC+00:00) Coordinated Universal Time',
        'Asia/Kolkata' => '(UTC+05:30) Mumbai, New Delhi, Kolkata',
        'Asia/Dubai' => '(UTC+04:00) Dubai, Abu Dhabi, Muscat',
        'Asia/Riyadh' => '(UTC+03:00) Riyadh, Saudi Arabia',
        'Asia/Singapore' => '(UTC+08:00) Singapore, Kuala Lumpur',
        'Asia/Tokyo' => '(UTC+09:00) Tokyo, Osaka, Sapporo',
        'Europe/London' => '(UTC+00:00 / BST) London, Edinburgh',
        'Europe/Berlin' => '(UTC+01:00 / CEST) Berlin, Frankfurt, Paris',
        'America/New_York' => '(UTC-05:00 / EDT) New York, Eastern Time',
        'America/Chicago' => '(UTC-06:00 / CDT) Chicago, Central Time',
        'America/Los_Angeles' => '(UTC-08:00 / PDT) Los Angeles, Pacific Time',
        'Australia/Sydney' => '(UTC+10:00 / AEST) Sydney, Melbourne',
    ];

    /**
     * Curated supported locales.
     */
    private const LOCALES = [
        'en' => ['name' => 'English (US)', 'native' => 'English (US)', 'dir' => 'ltr'],
        'bn' => ['name' => 'Bengali', 'native' => 'বাংলা (বাংলাদেশ)', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'dir' => 'ltr'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'dir' => 'ltr'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'dir' => 'ltr'],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'dir' => 'ltr'],
    ];

    /**
     * Display Localization & Regional Settings Console.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $settings = $this->loadSettings();

        // Calculate live formatted preview sample
        try {
            $nowInTimezone = Carbon::now($settings['timezone']);
            $currentTimeStr = $settings['time_format'] === '24h' 
                ? $nowInTimezone->format('H:i:s') 
                : $nowInTimezone->format('h:i:s A');
            $currentDateStr = $nowInTimezone->format($this->convertPhpDateFormat($settings['date_format']));
        } catch (\Throwable $e) {
            $currentTimeStr = now()->format('h:i:s A');
            $currentDateStr = now()->format('d/m/Y');
        }

        // Format sample money
        $sampleAmountNumber = 1250.75;
        $formattedSampleAmount = $this->formatSampleCurrency(
            $sampleAmountNumber, 
            $settings['currency_code'], 
            $settings['currency_symbol'], 
            $settings['currency_position'], 
            $settings['decimal_separator'], 
            $settings['thousand_separator'], 
            $settings['decimal_precision']
        );

        $stats = [
            'currency_code' => $settings['currency_code'],
            'currency_symbol' => $settings['currency_symbol'],
            'currency_display' => "{$settings['currency_code']} ({$settings['currency_symbol']})",
            'timezone' => $settings['timezone'],
            'current_time' => $currentTimeStr,
            'current_date' => $currentDateStr,
            'default_locale' => $settings['default_locale'],
            'default_locale_native' => self::LOCALES[$settings['default_locale']]['native'] ?? 'English (US)',
            'formatted_sample_amount' => $formattedSampleAmount,
            'first_day_of_week' => ucfirst($settings['first_day_of_week']),
        ];

        return Inertia::render('Admin/Settings/Localization/Index', [
            'settings' => $settings,
            'stats' => $stats,
            'timezones' => self::POPULAR_TIMEZONES,
            'locales' => self::LOCALES,
        ]);
    }

    /**
     * Update Localization Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_position' => ['required', 'string', 'in:before,after'],
            'decimal_separator' => ['required', 'string', Rule::in(['.', ','])],
            'thousand_separator' => ['required', 'string', Rule::in([',', '.', 'space', 'none'])],
            'decimal_precision' => ['required', 'integer', 'min:0', 'max:4'],
            'timezone' => ['required', 'string', 'timezone'],
            'date_format' => ['required', 'string', 'in:d/m/Y,Y-m-d,m/d/Y,d M Y,j F Y'],
            'time_format' => ['required', 'string', 'in:12h,24h'],
            'first_day_of_week' => ['required', 'string', 'in:sunday,monday,saturday'],
            'default_locale' => ['required', 'string', 'in:en,bn,ar,es,de,fr,hi'],
            'allow_client_language' => ['required', 'boolean'],
            'rtl_support' => ['required', 'boolean'],
            'exchange_rates' => ['nullable', 'array'],
            'exchange_rates.*.code' => ['required', 'string', 'size:3'],
            'exchange_rates.*.symbol' => ['required', 'string', 'max:10'],
            'exchange_rates.*.rate' => ['required', 'numeric', 'min:0.000001'],
            'exchange_rates.*.enabled' => ['required', 'boolean'],
        ]);

        $validated['currency_code'] = strtoupper($validated['currency_code']);

        foreach ($validated as $key => $value) {
            SystemSetting::set('localization.' . $key, $value, 'localization');
        }

        // Also sync legacy top-level keys for backward compatibility
        SystemSetting::set('currency', $validated['currency_code'], 'general');
        SystemSetting::set('currency_symbol', $validated['currency_symbol'], 'general');

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'update_localization_settings',
            'description' => 'Updated system timezone, currency rules, and locale configuration',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Localization Console',
            'new_values' => [
                'currency_code' => $validated['currency_code'],
                'currency_symbol' => $validated['currency_symbol'],
                'timezone' => $validated['timezone'],
                'default_locale' => $validated['default_locale'],
            ],
        ]);

        return redirect()->back()->with('success', 'Localization and regional formats saved successfully.');
    }

    /**
     * Reset Localization Settings to factory defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        foreach (self::DEFAULTS as $key => $value) {
            SystemSetting::set('localization.' . $key, $value, 'localization');
        }

        SystemSetting::set('currency', self::DEFAULTS['currency_code'], 'general');
        SystemSetting::set('currency_symbol', self::DEFAULTS['currency_symbol'], 'general');

        ActivityLog::create([
            'user_id' => auth()->id() ?: 1,
            'action' => 'reset_localization_settings',
            'description' => 'Reset localization and currency settings to factory defaults',
            'ip_address' => request()->ip() ?: '127.0.0.1',
            'user_agent' => request()->userAgent() ?: 'Localization Console',
        ]);

        return redirect()->back()->with('success', 'Localization settings reset to factory defaults.');
    }

    /**
     * Load current settings with fallbacks.
     */
    private function loadSettings(): array
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $val = SystemSetting::get('localization.' . $key, $default);

            if (is_bool($default)) {
                $val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_int($default)) {
                $val = (int) $val;
            }

            $settings[$key] = $val;
        }

        return $settings;
    }

    private function convertPhpDateFormat(string $format): string
    {
        return match ($format) {
            'Y-m-d' => 'Y-m-d',
            'm/d/Y' => 'm/d/Y',
            'd M Y' => 'd M Y',
            'j F Y' => 'j F Y',
            default => 'd/m/Y',
        };
    }

    private function formatSampleCurrency($amount, $code, $symbol, $position, $decSep, $thouSep, $precision): string
    {
        $tSep = match ($thouSep) {
            'none' => '',
            'space' => ' ',
            '.' => '.',
            default => ',',
        };

        $num = number_format((float)$amount, (int)$precision, $decSep, $tSep);

        return $position === 'after' ? "{$num} {$symbol}" : "{$symbol} {$num}";
    }
}
