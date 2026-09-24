<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Top-level generic /login redirecting to ISP Tenant Portal Login
Route::get('/login', fn() => redirect()->route('tenant.login'))->name('login');

// Global Language / Locale Switcher Route
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['bn', 'en'])) {
        session(['locale' => $lang]);
    }
    return back();
})->name('locale.switch');

// Load Dedicated Platform Owner Routes
require __DIR__.'/owner.php';

// Load ISP Tenant Routes (Billing, Subscriptions, Support Tickets)
require __DIR__.'/tenant.php';

// Load ISP Reseller / Sub-ISP Partner Routes
require __DIR__.'/reseller.php';


