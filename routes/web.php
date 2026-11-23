<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Top-level generic /login redirecting to ISP Tenant Portal Login
Route::get('/login', fn() => redirect()->route('tenant.login'))->name('login');

// Canonical customer-facing URL: /{tenant-slug}/login
Route::prefix('{tenant:slug}')->name('customer.portal.clean.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\CustomerPortal\CustomerPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\CustomerPortal\CustomerPortalAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::middleware([\App\Http\Middleware\EnsureCustomerAccount::class, \App\Http\Middleware\CustomerPortalNoStore::class])->group(function () {
        Route::get('/', function (\App\Models\Tenant $tenant) {
            return view('customer-portal.dashboard', compact('tenant'));
        })->name('dashboard');
    });
});

Route::prefix('portal/{tenant:slug}')->name('customer.portal.')->group(function () {
    Route::get('/manifest.webmanifest', function (\App\Models\Tenant $tenant) {
        return response()->json([
            'name' => ($tenant->company_name ?? $tenant->name) . ' Customer Portal',
            'short_name' => $tenant->name,
            'start_url' => route('customer.portal.dashboard', ['tenant' => $tenant->slug]),
            'scope' => '/portal/' . $tenant->slug . '/',
            'display' => 'standalone',
            'background_color' => '#f1f5f9',
            'theme_color' => '#0891b2',
            'icons' => [],
        ])->header('Cache-Control', 'public, max-age=3600');
    })->name('manifest');
    Route::get('/login', [\App\Http\Controllers\CustomerPortal\CustomerPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\CustomerPortal\CustomerPortalAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::middleware([\App\Http\Middleware\EnsureCustomerAccount::class, \App\Http\Middleware\CustomerPortalNoStore::class])->group(function () {
        Route::get('/', function (\App\Models\Tenant $tenant) {
            return view('customer-portal.dashboard', compact('tenant'));
        })->name('dashboard');
        Route::get('/bills', [\App\Http\Controllers\CustomerPortal\CustomerPortalBillingController::class, 'invoices'])->name('invoices');
        Route::get('/bills/{invoice}', [\App\Http\Controllers\CustomerPortal\CustomerPortalBillingController::class, 'showInvoice'])->whereNumber('invoice')->name('invoice');
        Route::post('/bills/{invoice}/payment-intent', [\App\Http\Controllers\CustomerPortal\CustomerPortalBillingController::class, 'createPaymentIntent'])->middleware('throttle:5,1')->whereNumber('invoice')->name('payment-intent');
        Route::get('/package', [\App\Http\Controllers\CustomerPortal\CustomerPortalServicesController::class, 'package'])->name('package');
        Route::get('/payments', [\App\Http\Controllers\CustomerPortal\CustomerPortalServicesController::class, 'payments'])->name('payments');
        Route::get('/support', [\App\Http\Controllers\CustomerPortal\CustomerPortalServicesController::class, 'tickets'])->name('tickets');
        Route::post('/support', [\App\Http\Controllers\CustomerPortal\CustomerPortalServicesController::class, 'createTicket'])->middleware('throttle:5,1')->name('ticket.store');
        Route::get('/support/{ticket}', [\App\Http\Controllers\CustomerPortal\CustomerPortalServicesController::class, 'ticket'])->whereNumber('ticket')->name('ticket');
        Route::post('/logout', [\App\Http\Controllers\CustomerPortal\CustomerPortalAuthController::class, 'logout'])->name('logout');
    });
});

// Global Language / Locale Switcher Route
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['bn', 'en'])) {
        session([
            'locale' => $lang,
            'locale_explicit' => true,
            'locale_explicit_at' => now()->toIso8601String(),
        ]);
    }
    return back();
})->name('locale.switch');

// Load Dedicated Platform Owner Routes
require __DIR__.'/owner.php';

// Load ISP Tenant Routes (Billing, Subscriptions, Support Tickets)
require __DIR__.'/tenant.php';

// Load ISP Reseller / Sub-ISP Partner Routes
require __DIR__.'/reseller.php';


