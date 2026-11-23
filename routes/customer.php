<?php

use App\Http\Controllers\CustomerPortal\CustomerPortalAuthController;
use App\Http\Controllers\CustomerPortal\CustomerPortalBillingController;
use App\Http\Controllers\CustomerPortal\CustomerPortalServicesController;
use App\Http\Middleware\CustomerPortalNoStore;
use App\Http\Middleware\EnsureCustomerAccount;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

// Canonical customer-facing URL: /{tenant-slug}/login
Route::prefix('{tenant:slug}')->name('customer.portal.clean.')->group(function () {
    Route::get('/login', [CustomerPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerPortalAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::middleware([EnsureCustomerAccount::class, CustomerPortalNoStore::class])->group(function () {
        Route::get('/', function (Tenant $tenant) {
            return view('customer-portal.dashboard', compact('tenant'));
        })->name('dashboard');
    });
});

// Legacy /portal/{tenant} routes remain available for existing bookmarks.
Route::prefix('portal/{tenant:slug}')->name('customer.portal.')->group(function () {
    Route::get('/manifest.webmanifest', function (Tenant $tenant) {
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
    Route::get('/login', [CustomerPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerPortalAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
    Route::middleware([EnsureCustomerAccount::class, CustomerPortalNoStore::class])->group(function () {
        Route::get('/', function (Tenant $tenant) {
            return view('customer-portal.dashboard', compact('tenant'));
        })->name('dashboard');
        Route::get('/bills', [CustomerPortalBillingController::class, 'invoices'])->name('invoices');
        Route::get('/bills/{invoice}', [CustomerPortalBillingController::class, 'showInvoice'])->whereNumber('invoice')->name('invoice');
        Route::post('/bills/{invoice}/payment-intent', [CustomerPortalBillingController::class, 'createPaymentIntent'])->middleware('throttle:5,1')->whereNumber('invoice')->name('payment-intent');
        Route::get('/package', [CustomerPortalServicesController::class, 'package'])->name('package');
        Route::get('/payments', [CustomerPortalServicesController::class, 'payments'])->name('payments');
        Route::get('/support', [CustomerPortalServicesController::class, 'tickets'])->name('tickets');
        Route::post('/support', [CustomerPortalServicesController::class, 'createTicket'])->middleware('throttle:5,1')->name('ticket.store');
        Route::get('/support/{ticket}', [CustomerPortalServicesController::class, 'ticket'])->whereNumber('ticket')->name('ticket');
        Route::post('/logout', [CustomerPortalAuthController::class, 'logout'])->name('logout');
    });
});
