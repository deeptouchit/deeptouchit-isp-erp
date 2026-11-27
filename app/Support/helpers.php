<?php

use App\Models\Setting;

if (! function_exists('currency')) {
    /**
     * Format an amount using the configured global currency settings.
     */
    function currency(int|float|string|null $amount): string
    {
        $symbol = (string) Setting::get('currency_symbol', '৳');
        $position = (string) Setting::get('currency_position', 'left');
        $decimals = (int) Setting::get('currency_decimals', 2);
        $thousandSeparator = (string) Setting::get('thousand_separator', ',');
        $decimalSeparator = (string) Setting::get('decimal_separator', '.');

        if ($thousandSeparator === 'space') {
            $thousandSeparator = ' ';
        }

        $value = is_numeric($amount)
            ? number_format((float) $amount, $decimals, $decimalSeparator, $thousandSeparator)
            : (string) ($amount ?? '0');

        return $position === 'right'
            ? $value . ' ' . $symbol
            : $symbol . ' ' . $value;
    }
}
